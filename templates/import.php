<?php
$pageTitle = 'Import GIF';
$hideSearch = true;

$success = null;
$error = null;
$proxyPath = null;

if ($result !== null) {
    if (str_starts_with($result, 'SUCCESS:')) {
        $proxyPath = substr($result, 8);
        $success = 'GIF imported successfully!';
    } else {
        $error = $result;
    }
}

require __DIR__ . '/header.php';
?>

<h1 class="page-title">Import GIF by URL</h1>

<div style="background:#fff;border:1px solid #d0d8e8;border-radius:8px;padding:30px;box-shadow:0 1px 4px rgba(0,0,0,0.08);max-width:600px;margin:0 auto;">
    <?php if ($success && $proxyPath): ?>
        <div class="flash-success"><?= $success ?></div>
        <div style="text-align:center;margin-bottom:20px;">
            <a href="/gif/<?= htmlspecialchars($proxyPath) ?>">
                <img src="/g/<?= htmlspecialchars($proxyPath) ?>" style="max-width:300px;border-radius:6px;">
            </a>
            <p style="margin-top:12px;">
                <a href="/gif/<?= htmlspecialchars($proxyPath) ?>" style="color:#4a90d9;">View GIF page</a>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="flash-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="/import">
        <label for="gif_url">GIF URL</label>
        <input type="url" name="gif_url" id="gif_url" placeholder="https://example.com/image.gif" value="<?= htmlspecialchars($_POST['gif_url'] ?? '') ?>" required style="width:100%;padding:10px;border:2px solid #c0c8d8;border-radius:6px;font-size:14px;margin-bottom:18px;outline:none;">

        <label for="title">Title (optional)</label>
        <input type="text" name="title" id="title" placeholder="Funny cat" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" style="width:100%;padding:10px;border:2px solid #c0c8d8;border-radius:6px;font-size:14px;margin-bottom:18px;outline:none;">

        <label for="keywords">Keywords (optional)</label>
        <input type="text" name="keywords" id="keywords" placeholder="cat, funny, animals" value="<?= htmlspecialchars($_POST['keywords'] ?? '') ?>" style="width:100%;padding:10px;border:2px solid #c0c8d8;border-radius:6px;font-size:14px;margin-bottom:18px;outline:none;">
        <div style="font-size:12px;color:#888;margin-top:-14px;margin-bottom:18px;">Comma-separated.</div>

        <input type="submit" value="Import GIF" style="padding:12px 36px;background:linear-gradient(to bottom,#4a90d9,#357abd);border:2px solid #2a5f9e;border-radius:6px;color:#fff;font-size:16px;font-weight:bold;cursor:pointer;text-shadow:1px 1px 0 #2a5f9e;">
    </form>

    <div style="margin-top:20px;background:#e8f0fe;border-radius:6px;padding:12px;font-size:13px;color:#555;">
        <strong>Total GIFs in library:</strong> <?= (int)$totalGifs ?>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
