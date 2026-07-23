<?php
require_once '../config/db.php';
require_once '../config/session.php';

if (isAdminLoggedIn()) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $db   = getDB();
        $stmt = $db->prepare('SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Normalise $2b$ (bcryptjs/Node) → $2y$ so PHP password_verify works
        $hash = $user['password_hash'] ?? '';
        if (str_starts_with($hash, '$2b$')) {
            $hash = '$2y$' . substr($hash, 4);
        }

        if ($user && password_verify($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_user'] = $username;
            header('Location: index.php');
            exit;
        }
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login – TechSpace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body class="login-body">

<div class="login-wrap">
  <div class="login-card">
    <div class="login-brand">
      <span class="logo-icon">⚡</span>
      <h1>TechSpace</h1>
      <p>Admin Panel</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="login-form">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required
               autocomplete="username" placeholder="admin"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required
               autocomplete="current-password" placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary btn-full">Login →</button>
    </form>

    <p class="login-back"><a href="../index.php">← Back to site</a></p>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
