<?php
$pageTitle = 'Upload GIF';
$hideSearch = true;
require __DIR__ . '/header.php';
?>

<h1 class="page-title">Upload a GIF</h1>

<div class="upload-box">
    <form action="/upload" method="POST" enctype="multipart/form-data">
        <label for="title">Title</label>
        <input type="text" name="title" id="title" placeholder="e.g. Funny cat dancing" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">

        <label for="keywords">Keywords</label>
        <input type="text" name="keywords" id="keywords" placeholder="cat, funny, animals, dance" value="<?= htmlspecialchars($_POST['keywords'] ?? '') ?>">
        <div class="hint">Comma-separated. These help people find your GIF in search.</div>

        <label for="image">GIF file (max 32 MB)</label>
        <input type="file" name="image" id="image" accept=".gif,image/gif" required>

        <p style="font-size:12px;color:#888;margin-bottom:18px;">
            By uploading, you agree to our <a href="/rules" style="color:#4a90d9;">upload rules</a>.
        </p>

        <input type="submit" value="Upload!">
    </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>
