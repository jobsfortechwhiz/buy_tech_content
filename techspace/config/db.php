<?php
// ─── Database Configuration ─────────────────────────────────────────────────
// ⚠️  Change these values to match your InfinityFree phpMyAdmin credentials
// Found at: cpanel → MySQL Databases → your DB details

define('DB_HOST', 'sql.infinityfree.com');   // your MySQL host (from cpanel)
define('DB_NAME', 'your_database_name');     // e.g. if0_12345678_techspace
define('DB_USER', 'your_db_username');       // e.g. if0_12345678
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// ─── Create PDO connection ───────────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#b91c1c;background:#fee2e2;border-radius:.5rem;max-width:600px;margin:2rem auto">'
              . '<h2>⚠️ Database Connection Failed</h2>'
              . '<p>Please update <code>config/db.php</code> with your InfinityFree MySQL credentials.</p>'
              . '<p style="font-size:.85rem;opacity:.7">' . htmlspecialchars($e->getMessage()) . '</p>'
              . '</div>');
        }
    }
    return $pdo;
}
