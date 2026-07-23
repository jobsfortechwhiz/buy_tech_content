<?php
$page_title  = 'Entries';
$admin_panel = true;
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../includes/file-parser.php';
requireAdmin();

$db = getDB();

// ── Re-parse action ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reparse') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request.'];
        header('Location: entries.php'); exit;
    }
    $docId = (int)($_POST['doc_id'] ?? 0);
    $doc   = $db->prepare('SELECT * FROM documents WHERE id = ?');
    $doc->execute([$docId]);
    $doc = $doc->fetch();

    if ($doc) {
        $entries = extractNumberedEntries($doc['content']);
        $db->prepare('DELETE FROM entries WHERE document_id = ?')->execute([$docId]);
        $ins = $db->prepare('INSERT INTO entries (document_id, entry_number, content, created_at) VALUES (?, ?, ?, NOW())');
        foreach ($entries as $e) {
            $ins->execute([$docId, $e['number'], $e['content']]);
        }
        $_SESSION['flash'] = ['type' => 'success', 'msg' => count($entries) . ' entries re-parsed from "' . $doc['title'] . '".'];
    }
    header('Location: entries.php'); exit;
}

// ── Delete single entry ───────────────────────────────────────────────────────
if (isset($_GET['delete']) && verifyCsrf($_GET['csrf'] ?? '')) {
    $db->prepare('DELETE FROM entries WHERE id = ?')->execute([(int)$_GET['delete']]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Entry deleted.'];
    header('Location: entries.php'); exit;
}

// ── Fetch data ────────────────────────────────────────────────────────────────
$selectedDoc = (int)($_GET['doc'] ?? 0);
$documents   = $db->query('SELECT id, title FROM documents ORDER BY created_at DESC')->fetchAll();
$totalEntries= $db->query('SELECT COUNT(*) FROM entries')->fetchColumn();

$entryQuery  = $selectedDoc
    ? $db->prepare('SELECT e.*, d.title AS doc_title FROM entries e JOIN documents d ON d.id=e.document_id WHERE e.document_id=? ORDER BY e.entry_number')
    : $db->prepare('SELECT e.*, d.title AS doc_title FROM entries e JOIN documents d ON d.id=e.document_id ORDER BY e.entry_number');
$selectedDoc ? $entryQuery->execute([$selectedDoc]) : $entryQuery->execute();
$entries = $entryQuery->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Entries – TechSpace Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body class="admin-body">

<header class="site-header">
  <div class="container header-inner">
    <a href="../index.php" class="logo"><span class="logo-icon">⚡</span>TechSpace</a>
    <nav class="header-nav">
      <a href="index.php"   class="nav-link">Dashboard</a>
      <a href="upload.php"  class="nav-link">Upload</a>
      <a href="entries.php" class="nav-link active">Entries</a>
      <a href="../index.php" class="nav-link" target="_blank">View Site ↗</a>
      <a href="logout.php"  class="nav-link logout-link">Logout</a>
    </nav>
  </div>
</header>

<div class="admin-hero">
  <div class="container">
    <h1 class="admin-title">Numbered <span>Entries</span></h1>
    <p class="admin-sub"><?= $totalEntries ?> total entries stored across <?= count($documents) ?> document(s).</p>
  </div>
</div>

<div class="section">
  <div class="container">

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" id="flash-msg"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- Re-parse controls -->
    <?php if ($documents): ?>
    <div class="entries-controls">
      <div class="entries-filter">
        <label for="docFilter">Filter by document:</label>
        <select id="docFilter" onchange="location='entries.php'+(this.value?'?doc='+this.value:'')">
          <option value="">All documents</option>
          <?php foreach ($documents as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $selectedDoc == $d['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if ($selectedDoc): ?>
      <form method="POST" action="entries.php" class="reparse-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="reparse">
        <input type="hidden" name="doc_id" value="<?= $selectedDoc ?>">
        <button type="submit" class="btn btn-outline"
                onclick="return confirm('Re-parse will replace all entries for this document.')">
          ↺ Re-parse Entries
        </button>
      </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Entries table -->
    <?php if (!$entries): ?>
      <div class="empty-state">
        <div class="empty-icon">🔢</div>
        <h3>No entries found</h3>
        <p>Upload a document with numbered lines (1. text, 2. text …) and they'll appear here.</p>
        <a href="upload.php" class="btn btn-primary" style="margin-top:1rem">Upload Document</a>
      </div>
    <?php else: ?>
      <div class="admin-section-header">
        <h2><?= count($entries) ?> entr<?= count($entries) === 1 ? 'y' : 'ies' ?></h2>
        <a href="../index.php" class="btn btn-outline" target="_blank">Preview User View ↗</a>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width:60px">#</th>
              <th>Content</th>
              <th style="width:180px">Document</th>
              <th style="width:80px">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($entries as $e): ?>
            <tr>
              <td class="td-num"><?= $e['entry_number'] ?></td>
              <td><?= htmlspecialchars($e['content']) ?></td>
              <td class="td-file"><?= htmlspecialchars($e['doc_title']) ?></td>
              <td>
                <a href="entries.php?delete=<?= $e['id'] ?>&csrf=<?= csrfToken() ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Delete entry <?= $e['entry_number'] ?>?')">Del</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </div>
</div>

<footer class="site-footer">
  <div class="container footer-inner">
    <p class="footer-brand">⚡ TechSpace Admin</p>
    <p class="footer-copy">&copy; <?= date('Y') ?> TechSpace</p>
  </div>
</footer>
<script src="../assets/js/main.js"></script>
</body>
</html>
