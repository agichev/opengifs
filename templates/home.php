<?php
$pageTitle = 'Browse GIFs';
require __DIR__ . '/header.php';
?>

<h1 class="page-title">Latest GIFs</h1>

<?php if (count($gifs) > 0): ?>
    <div class="gif-grid">
        <?php foreach ($gifs as $gif): ?>
            <a href="/gif/<?= htmlspecialchars($gif['proxy_path']) ?>" class="gif-card">
                <img src="/g/<?= htmlspecialchars($gif['proxy_path']) ?>" alt="<?= htmlspecialchars($gif['title'] ?? 'GIF') ?>" loading="lazy" decoding="async">
                <div class="gif-info">
                    <div class="gif-title"><?= htmlspecialchars($gif['title'] ?? 'Untitled') ?></div>
                    <div class="gif-meta"><?= (int)$gif['views'] ?> views</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="active"><span><?= $i ?></span></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

<?php else: ?>
    <div class="empty-state">
        <h2>No GIFs yet</h2>
        <p>Be the first to upload a GIF!</p>
        <br>
        <a href="/upload" style="display:inline-block;padding:12px 28px;background:linear-gradient(to bottom,#4a90d9,#357abd);border:2px solid #2a5f9e;border-radius:6px;color:#fff;font-weight:bold;text-decoration:none;text-shadow:1px 1px 0 #2a5f9e;">Upload a GIF</a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
