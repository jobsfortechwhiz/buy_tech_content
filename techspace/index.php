<?php
require_once 'config/db.php';

$db     = getDB();
$search = trim($_GET['q']    ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

// ── Build WHERE clause ────────────────────────────────────────────────────────
// Search matches: entry number (exact) OR entry text (LIKE)
$where  = '';
$params = [];

if ($search !== '') {
    if (ctype_digit($search)) {
        // Pure number → match by number OR text
        $where    = 'WHERE e.entry_number = :num OR e.content LIKE :q';
        $params   = [':num' => (int)$search, ':q' => "%$search%"];
    } else {
        $where    = 'WHERE e.content LIKE :q';
        $params   = [':q' => "%$search%"];
    }
}

// ── Total count (for pagination) ──────────────────────────────────────────────
$countSql = "SELECT COUNT(*) FROM entries e $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalFiltered = (int)$countStmt->fetchColumn();

$totalPages = $totalFiltered > 0 ? (int)ceil($totalFiltered / $limit) : 1;
$page       = min($page, $totalPages); // clamp in case URL is out of range

// Recalculate offset after clamp
$offset = ($page - 1) * $limit;

// ── Fetch page of entries ──────────────────────────────────────────────────────
$sql = "SELECT e.entry_number, e.content
        FROM entries e
        $where
        ORDER BY e.entry_number
        LIMIT :lim OFFSET :off";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':off',  $offset, PDO::PARAM_INT);
$stmt->execute();
$entries = $stmt->fetchAll();

// ── Meta ──────────────────────────────────────────────────────────────────────
$totalEntries = (int)$db->query('SELECT COUNT(*) FROM entries')->fetchColumn();
$docTitle     = $db->query('SELECT title FROM documents ORDER BY created_at DESC LIMIT 1')->fetchColumn();

// ── Pagination URL helper ──────────────────────────────────────────────────────
function pageUrl(int $p, string $q): string {
    $parts = ['page=' . $p];
    if ($q !== '') $parts[] = 'q=' . urlencode($q);
    return 'index.php?' . implode('&', $parts);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TechSpace – <?= htmlspecialchars($docTitle ?: 'Entries') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body class="user-body">

<!-- ─── Header ────────────────────────────────────────────────────────────── -->
<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo"><span class="logo-icon">⚡</span>TechSpace</a>
    <nav class="header-nav">
      <a href="index.php" class="nav-link active">Entries</a>
      <a href="admin/login.php" class="btn btn-sm">Admin Login</a>
    </nav>
  </div>
</header>

<!-- ─── Hero ──────────────────────────────────────────────────────────────── -->
<section class="user-hero">
  <div class="container">
    <h1 class="user-hero-title">
      <?= htmlspecialchars($docTitle ?: 'TechSpace') ?>
    </h1>
    <p class="user-hero-sub">
      <?= $totalEntries ?> numbered entr<?= $totalEntries != 1 ? 'ies' : 'y' ?> — browse or search below.
    </p>

    <!-- Search -->
    <form method="GET" action="index.php" class="search-form" id="searchForm">
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" name="q"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Search by number (e.g. 42) or keyword…"
               class="search-input" autocomplete="off" id="searchInput">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?>
          <a href="index.php" class="btn btn-outline">✕ Clear</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</section>

<!-- ─── Entries Table Card ────────────────────────────────────────────────── -->
<section class="section entries-section">
  <div class="container">

    <!-- Results summary -->
    <?php if ($search): ?>
      <p class="results-count">
        <?= $totalFiltered ?> result<?= $totalFiltered != 1 ? 's' : '' ?> for
        "<strong><?= htmlspecialchars($search) ?></strong>"
        &mdash; <a href="index.php">show all</a>
      </p>
    <?php elseif ($totalPages > 1): ?>
      <p class="results-count">
        Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $totalFiltered) ?> of <?= $totalEntries ?> entries
      </p>
    <?php endif; ?>

    <?php if (!$entries): ?>
      <div class="empty-state">
        <div class="empty-icon">🔢</div>
        <?php if ($search): ?>
          <h3>No entries matched your search</h3>
          <p>Try a different number or keyword. <a href="index.php">View all entries</a></p>
        <?php else: ?>
          <h3>No entries yet</h3>
          <p>The admin hasn't uploaded any numbered content yet.</p>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <!-- Card-table -->
      <div class="entries-card">

        <div class="entries-thead">
          <div class="eth-num">No.</div>
          <div class="eth-content">Entry</div>
        </div>

        <div class="entries-body">
          <?php foreach ($entries as $e): ?>
            <div class="entry-row">
              <div class="entry-num">
                <span class="num-badge"><?= (int)$e['entry_number'] ?></span>
              </div>
              <div class="entry-text">
                <?php
                  $text = htmlspecialchars($e['content']);
                  // Highlight search term if present and it's a text search
                  if ($search !== '' && !ctype_digit($search)) {
                      $hl   = htmlspecialchars($search);
                      $text = preg_replace(
                          '/(' . preg_quote($hl, '/') . ')/iu',
                          '<mark>$1</mark>',
                          $text
                      );
                  }
                  echo $text;
                ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="entries-tfoot">
          Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $totalFiltered) ?>
          of <?= $totalFiltered ?> entr<?= $totalFiltered != 1 ? 'ies' : 'y' ?>
        </div>
      </div>

      <!-- ─── Pagination ───────────────────────────────────────────────── -->
      <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Page navigation">

          <!-- Prev -->
          <?php if ($page > 1): ?>
            <a href="<?= pageUrl($page - 1, $search) ?>" class="page-btn" aria-label="Previous">&#8592; Prev</a>
          <?php else: ?>
            <span class="page-btn disabled">&#8592; Prev</span>
          <?php endif; ?>

          <!-- Page numbers -->
          <?php
            // Show: first page, last page, current ±2, with … gaps
            $shown = [];
            for ($i = 1; $i <= $totalPages; $i++) {
                if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2) {
                    $shown[] = $i;
                }
            }
            $prev = null;
            foreach ($shown as $p):
                if ($prev !== null && $p - $prev > 1): ?>
                  <span class="page-ellipsis">…</span>
                <?php endif; ?>
                <a href="<?= pageUrl($p, $search) ?>"
                   class="page-btn <?= $p === $page ? 'active' : '' ?>"
                   <?= $p === $page ? 'aria-current="page"' : '' ?>>
                  <?= $p ?>
                </a>
          <?php   $prev = $p;
            endforeach; ?>

          <!-- Next -->
          <?php if ($page < $totalPages): ?>
            <a href="<?= pageUrl($page + 1, $search) ?>" class="page-btn" aria-label="Next">Next &#8594;</a>
          <?php else: ?>
            <span class="page-btn disabled">Next &#8594;</span>
          <?php endif; ?>

        </nav>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</section>

<!-- ─── Footer ────────────────────────────────────────────────────────────── -->
<footer class="site-footer">
  <div class="container footer-inner">
    <p class="footer-brand">⚡ TechSpace</p>
    <p class="footer-copy">&copy; <?= date('Y') ?> TechSpace &mdash; techspaceforpurchaseonline.kesug.com</p>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
