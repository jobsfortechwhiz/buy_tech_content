<?php
$page_title  = 'Upload Document';
$admin_panel = true;
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../includes/file-parser.php';
requireAdmin();

$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category']    ?? '');
        $file        = $_FILES['document']        ?? null;

        if (!$title)
            $error = 'Document title is required.';
        elseif (!$file || $file['error'] === UPLOAD_ERR_NO_FILE)
            $error = 'Please select a file to upload.';
        elseif ($file['error'] !== UPLOAD_ERR_OK)
            $error = 'Upload failed (error code ' . $file['error'] . '). File may be too large.';
        else {
            $origName = $file['name'];
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, ['txt', 'docx'], true)) {
                $error = 'Only .txt and .docx files are supported.';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $error = 'File is too large. Maximum size is 10 MB.';
            } else {
                $parsed = parseUploadedFile($file['tmp_name'], $origName);
                if ($parsed['error']) {
                    $error = $parsed['error'];
                } elseif (!$parsed['content']) {
                    $error = 'The file appears to be empty.';
                } else {
                    $db = getDB();
                    $db->beginTransaction();
                    try {
                        // 1. Store full document
                        $stmt = $db->prepare(
                            'INSERT INTO documents (title, description, category, file_type, original_filename, content, created_at)
                             VALUES (?, ?, ?, ?, ?, ?, NOW())'
                        );
                        $stmt->execute([$title, $description, $category, $ext, $origName, $parsed['content']]);
                        $docId = (int) $db->lastInsertId();

                        // 2. Extract numbered entries and store each as a row
                        $entries     = extractNumberedEntries($parsed['content']);
                        $entryCount  = 0;
                        if ($entries) {
                            $ins = $db->prepare(
                                'INSERT INTO entries (document_id, entry_number, content, created_at)
                                 VALUES (?, ?, ?, NOW())'
                            );
                            foreach ($entries as $e) {
                                $ins->execute([$docId, $e['number'], $e['content']]);
                                $entryCount++;
                            }
                        }

                        $db->commit();

                        $msg = "\"$title\" uploaded — full content stored.";
                        if ($entryCount) {
                            $msg .= " $entryCount numbered entries extracted and saved to the entries table.";
                        } else {
                            $msg .= " No numbered entries found (entries table not populated).";
                        }
                        $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
                        header('Location: index.php');
                        exit;

                    } catch (Throwable $e) {
                        $db->rollBack();
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Upload Document – TechSpace</title>
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
      <a href="upload.php"  class="nav-link active">Upload</a>
      <a href="entries.php" class="nav-link">Entries</a>
      <a href="../index.php" class="nav-link" target="_blank">View Site ↗</a>
      <a href="logout.php"  class="nav-link logout-link">Logout</a>
    </nav>
  </div>
</header>

<div class="admin-hero">
  <div class="container">
    <h1 class="admin-title">Upload Document</h1>
    <p class="admin-sub">Upload a .docx or .txt file — full content + numbered entries stored in MySQL automatically.</p>
  </div>
</div>

<div class="section">
  <div class="container upload-wrap">

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="upload-card">
      <form method="POST" action="upload.php" enctype="multipart/form-data" class="upload-form" id="uploadForm">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="drop-zone" id="dropZone">
          <div class="drop-icon">📁</div>
          <p class="drop-text">Drag & drop your file here, or <label for="document" class="drop-browse">browse</label></p>
          <p class="drop-sub">Supports: <strong>.docx</strong> (Word) and <strong>.txt</strong> — max 10 MB</p>
          <input type="file" id="document" name="document" accept=".docx,.txt" required>
          <div class="drop-selected" id="dropSelected"></div>
        </div>

        <div class="form-group">
          <label for="title">Document Title <span class="req">*</span></label>
          <input type="text" id="title" name="title" required maxlength="200"
                 placeholder="e.g. Top 100 PHP Questions"
                 value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="category">Category <span class="muted">(optional)</span></label>
          <input type="text" id="category" name="category" maxlength="100"
                 placeholder="e.g. Questions, Sentences, Facts…"
                 value="<?= htmlspecialchars($_POST['category'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="description">Description <span class="muted">(optional)</span></label>
          <textarea id="description" name="description" rows="3"
                    placeholder="Short summary shown to users…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" id="submitBtn">Upload & Store</button>
          <a href="index.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>

    <div class="upload-note">
      <h3>How numbered entries work</h3>
      <ol>
        <li>Write your sentences as <code>1. text</code>, <code>2. text</code> … in Word or .txt</li>
        <li>Upload the file — content is parsed automatically</li>
        <li>Each numbered line is saved as its own row in the <code>entries</code> table</li>
        <li>Users see a clean numbered table on the public site</li>
      </ol>
    </div>

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
