<?php
/**
 * ─── Change Admin Password ─────────────────────────────────────────────────
 * 1. Upload this file to your server
 * 2. Visit it in your browser: https://techspace.ifree.page/db/create-admin.php
 * 3. Fill in the form to create / update the admin account
 * 4. DELETE this file from your server immediately after use!
 */

require_once '../config/db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (!$username || !$password)        $message = '❌ Username and password are required.';
    elseif ($password !== $confirm)       $message = '❌ Passwords do not match.';
    elseif (strlen($password) < 8)        $message = '❌ Password must be at least 8 characters.';
    else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db   = getDB();
        $db->prepare('INSERT INTO admin_users (username, password_hash)
                      VALUES (?, ?)
                      ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)')
           ->execute([$username, $hash]);
        $message = '✅ Admin account saved! <strong>Delete this file now.</strong>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Create Admin – TechSpace</title>
  <style>
    body{font-family:system-ui,sans-serif;background:#f5f5f8;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{background:#fff;padding:2.5rem;border-radius:1rem;box-shadow:0 4px 24px rgba(0,0,0,.08);width:400px;max-width:95vw}
    h2{margin:0 0 1.5rem;font-size:1.4rem}
    label{display:block;font-size:.9rem;font-weight:600;margin-bottom:.35rem;color:#374151}
    input{width:100%;padding:.7rem 1rem;border:1px solid #e5e7eb;border-radius:.5rem;font-size:1rem;margin-bottom:1.2rem;box-sizing:border-box}
    input:focus{outline:none;border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.12)}
    button{width:100%;padding:.85rem;background:#4f46e5;color:#fff;border:none;border-radius:.5rem;font-size:1rem;font-weight:600;cursor:pointer}
    button:hover{background:#3730a3}
    .msg{padding:.85rem 1rem;border-radius:.5rem;margin-bottom:1.25rem;font-size:.9rem;background:#d1fae5;color:#065f46}
    .msg.err{background:#fee2e2;color:#991b1b}
    .warn{background:#fef3c7;color:#92400e;padding:.75rem 1rem;border-radius:.5rem;font-size:.85rem;margin-bottom:1.5rem}
  </style>
</head>
<body>
<div class="box">
  <h2>⚡ Create / Update Admin</h2>
  <div class="warn">⚠️ Delete this file from your server after use!</div>
  <?php if ($message): ?><div class="msg"><?= $message ?></div><?php endif; ?>
  <form method="POST">
    <label>Username</label>
    <input type="text" name="username" value="admin" required>
    <label>New Password</label>
    <input type="password" name="password" required placeholder="Min 8 characters">
    <label>Confirm Password</label>
    <input type="password" name="confirm" required>
    <button type="submit">Save Admin Account</button>
  </form>
</div>
</body>
</html>
