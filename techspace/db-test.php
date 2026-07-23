<?php
/**
 * TechSpace — DB Connection Tester
 * Upload this to your server, visit it in browser, then DELETE it.
 * URL: https://techspaceforpurchaseonline.kesug.com/techspace/db-test.php
 */

// ── PASTE YOUR CREDENTIALS HERE ──────────────────────────────────────────────
$host = '';   // e.g. sql200.epizy.com  (from cPanel → MySQL Databases)
$name = '';   // e.g. epiz_12345678_techspace
$user = '';   // e.g. epiz_12345678
$pass = '';   // your DB password
// ─────────────────────────────────────────────────────────────────────────────

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>DB Test – TechSpace</title>
  <style>
    body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:2rem;margin:0}
    h1{color:#a5b4fc;font-size:1.4rem;margin-bottom:1.5rem}
    .box{background:#1e293b;border-radius:.75rem;padding:1.5rem;margin-bottom:1rem;border:1px solid #334155}
    .ok  {color:#34d399} .err{color:#f87171} .warn{color:#fbbf24}
    .label{font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.4rem}
    pre{background:#0f172a;padding:1rem;border-radius:.5rem;font-size:.82rem;overflow-x:auto;color:#fca5a5;margin-top:.5rem}
    .row{display:flex;gap:1rem;align-items:center;padding:.4rem 0;border-bottom:1px solid #334155}
    .row:last-child{border:none}
    .k{color:#94a3b8;min-width:180px;font-size:.88rem}
    .v{color:#e2e8f0;font-size:.88rem;word-break:break-all}
  </style>
</head>
<body>
<h1>⚡ TechSpace — Database Connection Test</h1>

<?php if (!$host || !$name || !$user): ?>
<div class="box">
  <div class="label">⚠️ Action needed</div>
  <p class="warn">Open <code>db-test.php</code> and fill in the credentials at the top of the file, then re-upload and refresh.</p>
</div>
<?php else: ?>

<!-- ── Server Info ── -->
<div class="box">
  <div class="label">Server Environment</div>
  <?php
  $info = [
    'PHP Version'            => phpversion(),
    'Server Software'        => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'PDO available'          => extension_loaded('pdo') ? '✅ Yes' : '❌ No',
    'PDO MySQL available'    => extension_loaded('pdo_mysql') ? '✅ Yes' : '❌ No — fatal issue',
    'ZipArchive available'   => class_exists('ZipArchive') ? '✅ Yes (needed for .docx)' : '⚠️ No — .docx upload will fail',
    'max_file_uploads'       => ini_get('max_file_uploads'),
    'upload_max_filesize'    => ini_get('upload_max_filesize'),
    'post_max_size'          => ini_get('post_max_size'),
    'DOCUMENT_ROOT'          => $_SERVER['DOCUMENT_ROOT'] ?? '—',
  ];
  foreach ($info as $k => $v): ?>
  <div class="row"><span class="k"><?= $k ?></span><span class="v"><?= htmlspecialchars($v) ?></span></div>
  <?php endforeach; ?>
</div>

<!-- ── Connection Test ── -->
<div class="box">
  <div class="label">Connection Test</div>
  <div class="row"><span class="k">Host</span><span class="v"><?= htmlspecialchars($host) ?></span></div>
  <div class="row"><span class="k">Database</span><span class="v"><?= htmlspecialchars($name) ?></span></div>
  <div class="row"><span class="k">Username</span><span class="v"><?= htmlspecialchars($user) ?></span></div>
  <div class="row"><span class="k">Password</span><span class="v"><?= str_repeat('•', strlen($pass)) ?></span></div>

  <?php
  $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
  try {
      $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
      $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
      echo '<div class="row" style="margin-top:.5rem"><span class="k">Status</span><span class="v ok">✅ Connected successfully!</span></div>';
      echo '<div class="row"><span class="k">MySQL version</span><span class="v">' . htmlspecialchars($ver) . '</span></div>';

      // Check tables
      $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
      echo '<div class="row"><span class="k">Tables found</span><span class="v">' . (count($tables) ? implode(', ', array_map('htmlspecialchars', $tables)) : '<span class="warn">None — run setup.sql first!</span>') . '</span></div>';

      if (in_array('admin_users', $tables)) {
          $count = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
          echo '<div class="row"><span class="k">Admin accounts</span><span class="v">' . ($count ? "✅ $count account(s) exist" : '<span class="warn">0 — no admin yet, run setup.sql</span>') . '</span></div>';
      }
      if (in_array('documents', $tables)) {
          $count = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
          echo '<div class="row"><span class="k">Documents in DB</span><span class="v">' . $count . '</span></div>';
      }

  } catch (PDOException $e) {
      echo '<div class="row" style="margin-top:.5rem"><span class="k">Status</span><span class="v err">❌ Connection failed</span></div>';
      echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';

      // Helpful hints based on error message
      $msg = $e->getMessage();
      echo '<div style="margin-top:1rem">';
      if (str_contains($msg, 'Access denied')) {
          echo '<p class="warn">👉 Wrong username or password. Double-check in cPanel → MySQL Databases.</p>';
      } elseif (str_contains($msg, 'Unknown database')) {
          echo '<p class="warn">👉 Database name is wrong or the database doesn\'t exist yet. Create it in cPanel first.</p>';
      } elseif (str_contains($msg, 'php_network') || str_contains($msg, 'getaddrinfo') || str_contains($msg, 'Connection refused')) {
          echo '<p class="warn">👉 <strong>Wrong hostname.</strong> The MySQL host is NOT your domain or localhost on this server.<br>Go to cPanel → MySQL Databases and look for the <em>MySQL hostname</em> field (e.g. <code>sql200.epizy.com</code> or <code>sql306.epizy.com</code>).</p>';
      } elseif (str_contains($msg, 'SQLSTATE[HY000] [2002]')) {
          echo '<p class="warn">👉 Cannot reach the MySQL server. The hostname is likely wrong.</p>';
      }
      echo '</div>';
  }
  ?>
</div>

<?php endif; ?>

<div class="box" style="border-color:#7f1d1d">
  <div class="label" style="color:#f87171">🔐 Security reminder</div>
  <p class="err">Delete this file from your server immediately after you've diagnosed the issue!</p>
</div>
</body>
</html>
