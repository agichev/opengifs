<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'OpenGifs') ?> — OpenGifs</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><text y='28' font-size='28'>🎞️</text></svg>">
</head>
<body>

<div class="header">
    <div class="header-inner">
        <a href="/" class="logo">Open<span>Gifs</span></a>
        <nav class="header-nav">
            <a href="/">Browse</a>
            <a href="/api">API</a>
            <a href="/rules">Rules</a>
            <a href="/import" style="background:rgba(255,255,255,0.1);">Import</a>
            <a href="/upload" class="upload-btn">+ Upload</a>
        </nav>
    </div>
</div>

<?php if (!isset($hideSearch)): ?>
<div class="search-container">
    <div class="search-inner">
        <form class="search-form" action="/search" method="GET">
            <input type="text" name="q" placeholder="Search GIFs..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" required>
            <button type="submit">Search</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="content">
    <?php if (!empty($success)): ?>
        <div class="flash-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="flash-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
