<?php
/**
 * Re-parse ALL existing documents into numbered entries.
 * Use this once to populate the entries table for documents
 * uploaded BEFORE the entries feature was added.
 *
 * Visit: /techspace/admin/reparse.php
 * DELETE this file after use.
 */
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../includes/file-parser.php';
requireAdmin();

$db   = getDB();
$docs = $db->query('SELECT id, title, content FROM documents ORDER BY id')->fetchAll();
$log  = [];

foreach ($docs as $doc) {
    $db->prepare('DELETE FROM entries WHERE document_id = ?')->execute([$doc['id']]);
    $entries = extractNumberedEntries($doc['content']);
    $ins     = $db->prepare(
        'INSERT INTO entries (document_id, entry_number, content, created_at) VALUES (?, ?, ?, NOW())'
    );
    foreach ($entries as $e) {
        $ins->execute([$doc['id'], $e['number'], $e['content']]);
    }
    $log[] = [
        'doc'     => $doc['title'],
        'entries' => count($entries),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Re-parse Results – TechSpace</title>
  <style>
    body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:2rem;margin:0}
    h1{color:#a5b4fc;margin-bottom:1.5rem}
    .box{background:#1e293b;border-radius:.75rem;padding:1.5rem;margin-bottom:1rem;border:1px solid #334155}
    .ok{color:#34d399}.warn{color:#fbbf24}
    .row{display:flex;gap:2rem;padding:.5rem 0;border-bottom:1px solid #334155;font-size:.9rem}
    .row:last-child{border:none}
    .num{color:#a5b4fc;font-weight:700;min-width:40px}
    a{color:#818cf8}
  </style>
</head>
<body>
<h1>⚡ Re-parse Complete</h1>
<div class="box">
  <?php foreach ($log as $l): ?>
    <div class="row">
      <span class="num"><?= $l['entries'] ?></span>
      <span><?= htmlspecialchars($l['doc']) ?> &mdash;
        <?php if ($l['entries']): ?>
          <span class="ok"><?= $l['entries'] ?> entries extracted</span>
        <?php else: ?>
          <span class="warn">0 numbered entries found</span>
        <?php endif; ?>
      </span>
    </div>
  <?php endforeach; ?>
</div>
<p><a href="index.php">← Back to Dashboard</a> &nbsp; <a href="../index.php" target="_blank">View User Panel ↗</a></p>
<p style="margin-top:1.5rem;color:#f87171;font-size:.85rem">⚠️ Delete this file from your server now.</p>
</body>
</html>
