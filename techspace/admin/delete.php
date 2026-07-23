<?php
require_once '../config/db.php';
require_once '../config/session.php';
requireAdmin();

$id   = (int)($_GET['id']   ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!$id || !verifyCsrf($csrf)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid delete request.'];
    header('Location: index.php'); exit;
}

$db   = getDB();
$stmt = $db->prepare('SELECT title FROM documents WHERE id = ?');
$stmt->execute([$id]);
$doc  = $stmt->fetch();

if (!$doc) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Document not found.'];
    header('Location: index.php'); exit;
}

$db->prepare('DELETE FROM documents WHERE id = ?')->execute([$id]);
$_SESSION['flash'] = ['type' => 'success', 'msg' => '"' . $doc['title'] . '" has been deleted.'];
header('Location: index.php');
exit;
