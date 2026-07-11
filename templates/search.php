<?php
$pageTitle = 'Search: ' . $query;
require __DIR__ . '/header.php';
?>

<h1 class="page-title">Search results for: "<?= htmlspecialchars($query) ?>"</h1>

<?php if (count($gifs) > 0): ?>
    <p style="color:#888;margin-bottom:18px;">Found <?= $total ?> GIF<?= $total !== 1 ? 's' : '' ?></p>
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
                <a href="?q=<?= htmlspecialchars($query) ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php else: ?>
    <div class="empty-state">
        <h2>No results found</h2>
        <p>Try different keywords.</p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
