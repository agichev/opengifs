<?php
$pageTitle = 'Browse GIFs';
require __DIR__ . '/header.php';
?>

<h1 class="page-title">Latest GIFs</h1>

<?php if (count($gifs) > 0): ?>
    <div class="gif-grid">
        <?php foreach ($gifs as $gif): ?>
            <a href="/gif/<?= htmlspecialchars($gif['proxy_path']) ?>" class="gif-card">
                <img src="/g/<?= htmlspecialchars($gif['proxy_path']) ?>.gif" alt="<?= htmlspecialchars($gif['title'] ?? 'GIF') ?>" loading="lazy" decoding="async">
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
        <p>Loading trending GIFs from GIPHY &amp; Tenor... <span id="parseStatus"></span></p>
        <div style="margin-top:16px;font-size:13px;color:#888;">
            <span id="parseSpinner" style="display:inline-block;width:16px;height:16px;border:2px solid #4a90d9;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;"></span>
            Please wait, this may take a moment.
        </div>
    </div>

    <script>
    (function() {
        var status = document.getElementById('parseStatus');
        var spinner = document.getElementById('parseSpinner');
        fetch('/parse').then(function(r) { return r.json(); }).then(function(d) {
            status.textContent = 'Imported in ' + d.time;
            spinner.style.display = 'none';
            if (d.success) setTimeout(function() { location.reload(); }, 1000);
        }).catch(function() {
            status.textContent = 'Failed to load';
            spinner.style.display = 'none';
        });
    })();
    </script>

    <style>
    @keyframes spin { to { transform: rotate(360deg); } }
    </style>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
