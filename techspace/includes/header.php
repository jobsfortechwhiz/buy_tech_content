<?php
$site_name = 'TechSpace';
$is_admin  = isset($admin_panel) && $admin_panel === true;
$base_path = $is_admin ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title ?? $site_name) ?> – <?= $site_name ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base_path ?>assets/css/style.css"/>
</head>
<body class="<?= $is_admin ? 'admin-body' : 'user-body' ?>">

<header class="site-header">
  <div class="container header-inner">
    <a href="<?= $is_admin ? '../index.php' : 'index.php' ?>" class="logo">
      <span class="logo-icon">⚡</span><?= $site_name ?>
    </a>
    <nav class="header-nav">
      <?php if ($is_admin): ?>
        <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="upload.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'upload.php' ? 'active' : '' ?>">Upload</a>
        <a href="../index.php" class="nav-link" target="_blank">View Site</a>
        <a href="logout.php" class="nav-link logout-link">Logout</a>
      <?php else: ?>
        <a href="index.php" class="nav-link active">Documents</a>
        <a href="admin/login.php" class="btn btn-sm">Admin Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
