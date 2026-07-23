<?php
$page_title  = 'Admin Dashboard';
$admin_panel = true;
require_once '../config/db.php';
require_once '../config/session.php';
requireAdmin();

$db = getDB();

// ─── Stats ───────────────────────────────────────────────────────────────────
$total     = $db->query('SELECT COUNT(*) FROM documents')->fetchColumn();
$wordCount = $db->query("SELECT COUNT(*) FROM documents WHERE file_type='docx'")->fetchColumn();
$txtCount  = $db->query("SELECT COUNT(*) FROM documents WHERE file_type='txt'")->fetchColumn();
$latest    = $db->query('SELECT title, created_at FROM documents ORDER BY created_at DESC LIMIT 1')->fetch();

// ─── All documents ───────────────────────────────────────────────────────────
$docs = $db->query('SELECT id, title, file_type, category, original_filename, created_at FROM documents ORDER BY created_at DESC')->fetchAll();

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard – TechSpace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body class="admin-body">

<header class="site-header">
  <div class="container header-inner">
    <a href="../index.php" class="logo"><span class="logo-icon">⚡</span>TechSpace</a>
    <nav class="header-nav">
      <a href="index.php"  class="nav-link active">Dashboard</a>
      <a href="upload.php" class="nav-link">Upload</a>
      <a href="../index.php" class="nav-link" target="_blank">View Site ↗</a>
      <a href="logout.php" class="nav-link logout-link">Logout</a>
    </nav>
  </div>
</header>

<div class="admin-hero">
  <div class="container">
    <h1 class="admin-title">Welcome back, <span><?= htmlspecialchars($_SESSION['admin_user']) ?></span> 👋</h1>
    <p class="admin-sub">Manage your uploaded documents from here.</p>
  </div>
</div>

<div class="section">
  <div class="container">

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" id="flash-msg"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-num"><?= $total ?></div>
        <div class="stat-label">Total Documents</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $wordCount ?></div>
        <div class="stat-label">Word (.docx) Files</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $txtCount ?></div>
        <div class="stat-label">Text (.txt) Files</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $latest ? date('M j', strtotime($latest['created_at'])) : '—' ?></div>
        <div class="stat-label">Last Upload</div>
      </div>
    </div>

    <!-- Quick upload -->
    <div class="admin-section-header">
      <h2>All Documents</h2>
      <a href="upload.php" class="btn btn-primary">+ Upload New</a>
    </div>

    <?php if (!$docs): ?>
      <div class="empty-state">
        <div class="empty-icon">📂</div>
        <h3>No documents uploaded yet</h3>
        <p>Click "Upload New" to add your first document.</p>
      </div>
    <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Type</th>
              <th>Category</th>
              <th>Original File</th>
              <th>Uploaded</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($docs as $i => $d): ?>
              <tr>
                <td class="td-num"><?= $i + 1 ?></td>
                <td class="td-title"><a href="../view.php?id=<?= $d['id'] ?>" target="_blank"><?= htmlspecialchars($d['title']) ?></a></td>
                <td><span class="doc-type-badge <?= $d['file_type'] === 'docx' ? 'badge-word' : 'badge-txt' ?>"><?= strtoupper($d['file_type']) ?></span></td>
                <td><?= $d['category'] ? htmlspecialchars($d['category']) : '<span class="muted">—</span>' ?></td>
                <td class="td-file"><?= htmlspecialchars($d['original_filename']) ?></td>
                <td><?= date('M j, Y', strtotime($d['created_at'])) ?></td>
                <td class="td-actions">
                  <a href="../view.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline" target="_blank">View</a>
                  <a href="delete.php?id=<?= $d['id'] ?>&csrf=<?= csrfToken() ?>"
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('Delete this document? This cannot be undone.')">Delete</a>
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
