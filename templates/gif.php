<?php
$pageTitle = $gif['title'] ?? 'GIF';
require __DIR__ . '/header.php';
?>

<div class="gif-single">
    <h1><?= htmlspecialchars($gif['title'] ?? 'Untitled GIF') ?></h1>

    <img src="/g/<?= htmlspecialchars($gif['proxy_path']) ?>" alt="<?= htmlspecialchars($gif['title'] ?? 'GIF') ?>">

    <div class="gif-details">
        <?php if (!empty($gif['keywords'])): ?>
            <div style="margin-bottom:10px;">
                <strong>Keywords:</strong>
                <?php foreach (explode(',', $gif['keywords']) as $keyword): ?>
                    <a href="/search?q=<?= htmlspecialchars(trim($keyword)) ?>" class="keyword-tag"><?= htmlspecialchars(trim($keyword)) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div>👁️ <?= (int)$gif['views'] ?> views</div>
        <div>📅 <?= date('M d, Y H:i', strtotime($gif['created_at'])) ?></div>
        <?php if ($gif['file_size'] > 0): ?>
            <div>💾 <?= round($gif['file_size'] / 1024, 1) ?> KB</div>
        <?php endif; ?>
    </div>

    <div class="direct-link-box">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">Direct GIF URL (proxied):</label>
        <input type="text" value="<?= $baseUrl ?>/g/<?= htmlspecialchars($gif['proxy_path']) ?>" readonly onclick="this.select()">
    </div>

    <div class="direct-link-box">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">Page URL:</label>
        <input type="text" value="<?= $baseUrl ?>/gif/<?= htmlspecialchars($gif['proxy_path']) ?>" readonly onclick="this.select()">
    </div>

    <div class="direct-link-box" style="background:#fff8e0;border-color:#e8d8a0;">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">HTML embed code:</label>
        <input type="text" value="&lt;img src=&quot;<?= $baseUrl ?>/g/<?= htmlspecialchars($gif['proxy_path']) ?>&quot; alt=&quot;<?= htmlspecialchars($gif['title'] ?? 'GIF') ?>&quot;&gt;" readonly onclick="this.select()">
    </div>

    <div class="direct-link-box" style="background:#e8f8f0;border-color:#a0d8b0;">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">Markdown embed code:</label>
        <input type="text" value="![<?= htmlspecialchars($gif['title'] ?? 'GIF') ?>](<?= $baseUrl ?>/g/<?= htmlspecialchars($gif['proxy_path']) ?>)" readonly onclick="this.select()">
    </div>

    <div class="direct-link-box" style="background:#f8f0e8;border-color:#d8c0a0;">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">BBCode embed (forums):</label>
        <input type="text" value="[img]<?= $baseUrl ?>/g/<?= htmlspecialchars($gif['proxy_path']) ?>[/img]" readonly onclick="this.select()">
    </div>
</div>

<?php if (!empty($related)): ?>
    <h2 class="section-title">More GIFs</h2>
    <div class="gif-grid">
        <?php foreach ($related as $rgif): ?>
            <a href="/gif/<?= htmlspecialchars($rgif['proxy_path']) ?>" class="gif-card">
                <img src="/g/<?= htmlspecialchars($rgif['proxy_path']) ?>" alt="<?= htmlspecialchars($rgif['title'] ?? 'GIF') ?>" loading="lazy">
                <div class="gif-info">
                    <div class="gif-title"><?= htmlspecialchars($rgif['title'] ?? 'Untitled') ?></div>
                    <div class="gif-meta"><?= (int)$rgif['views'] ?> views</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
