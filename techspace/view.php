<?php
require_once 'config/db.php';

$id  = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$db  = getDB();
$doc = $db->prepare('SELECT * FROM documents WHERE id = ?');
$doc->execute([$id]);
$doc = $doc->fetch();
if (!$doc) { header('Location: index.php'); exit; }

$page_title = $doc['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?> – TechSpace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body class="user-body">

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo"><span class="logo-icon">⚡</span>TechSpace</a>
    <nav class="header-nav">
      <a href="index.php" class="nav-link">← All Documents</a>
      <a href="admin/login.php" class="btn btn-sm">Admin Login</a>
    </nav>
  </div>
</header>

<section class="view-hero">
  <div class="container">
    <div class="view-meta">
      <span class="doc-type-badge <?= $doc['file_type'] === 'docx' ? 'badge-word' : 'badge-txt' ?>">
        <?= strtoupper($doc['file_type']) ?>
      </span>
      <?php if ($doc['category']): ?>
        <a href="index.php?cat=<?= urlencode($doc['category']) ?>" class="doc-category"><?= htmlspecialchars($doc['category']) ?></a>
      <?php endif; ?>
      <span class="doc-date">Uploaded <?= date('F j, Y \a\t g:i a', strtotime($doc['created_at'])) ?></span>
    </div>
    <h1 class="view-title"><?= htmlspecialchars($doc['title']) ?></h1>
    <?php if ($doc['description']): ?>
      <p class="view-desc"><?= htmlspecialchars($doc['description']) ?></p>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container view-body">
    <div class="doc-content-box">
      <?php
      $lines = explode("\n", $doc['content']);
      foreach ($lines as $line) {
          $line = trim($line);
          if ($line === '') {
              echo '<br>';
          } else {
              echo '<p>' . htmlspecialchars($line) . '</p>';
          }
      }
      ?>
    </div>
    <div class="view-actions">
      <a href="index.php" class="btn btn-outline">← Back to Documents</a>
    </div>
  </div>
</section>

<footer class="site-footer">
  <div class="container footer-inner">
    <p class="footer-brand">⚡ TechSpace</p>
    <p class="footer-copy">&copy; <?= date('Y') ?> TechSpace &mdash; techspace.ifree.page</p>
  </div>
</footer>
<script src="assets/js/main.js"></script>
</body>
</html>
