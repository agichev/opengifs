<?php
$pageTitle = 'Import GIFs';
$hideSearch = true;
$result = $_SESSION['parse_result'] ?? null;
unset($_SESSION['parse_result']);
require __DIR__ . '/header.php';
?>

<h1 class="page-title">Import GIFs</h1>

<div style="background:#fff;border:1px solid #d0d8e8;border-radius:8px;padding:30px;box-shadow:0 1px 4px rgba(0,0,0,0.08);max-width:500px;margin:0 auto;text-align:center;">
    <p style="font-size:14px;color:#555;margin-bottom:24px;line-height:1.7;">
        Fetch trending GIFs from Klipy and GIPHY.<br>
        No API keys needed.
    </p>

    <?php if ($result): ?>
        <div style="padding:12px;border-radius:6px;margin-bottom:16px;font-weight:600;background:<?= $result['success'] ? '#d4edda' : '#f8d7da' ?>;color:<?= $result['success'] ? '#155724' : '#721c24' ?>;">
            <?= htmlspecialchars($result['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($remaining > 0): ?>
        <p style="color:#888;font-size:14px;margin-bottom:12px;">Next import available in <span id="timerDisplay"><?= floor($remaining / 60) ?>:<?= str_pad($remaining % 60, 2, '0', STR_PAD_LEFT) ?></span></p>
        <div style="width:100%;padding:16px;background:#ccc;border-radius:8px;color:#666;font-size:18px;font-weight:bold;">Please wait</div>
    <?php else: ?>
        <form method="POST" action="/parse-page">
            <button type="submit" style="width:100%;padding:16px;background:linear-gradient(to bottom,#4a90d9,#357abd);border:2px solid #2a5f9e;border-radius:8px;color:#fff;font-size:18px;font-weight:bold;cursor:pointer;">
                Import GIFs Now
            </button>
        </form>
    <?php endif; ?>

    <div style="margin-top:20px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
        <div style="background:#e8f0fe;border-radius:6px;padding:12px;">
            <div style="font-size:22px;font-weight:bold;color:#2a5f9e;"><?= (int)$totalGifs ?></div>
            <div style="font-size:11px;color:#888;">Total</div>
        </div>
        <div style="background:#e8f0fe;border-radius:6px;padding:12px;">
            <div style="font-size:22px;font-weight:bold;color:#2a5f9e;"><?= (int)$giphyCount ?></div>
            <div style="font-size:11px;color:#888;">GIPHY</div>
        </div>
        <div style="background:#e8f0fe;border-radius:6px;padding:12px;">
            <div style="font-size:22px;font-weight:bold;color:#2a5f9e;"><?= (int)$klipyCount ?></div>
            <div style="font-size:11px;color:#888;">Klipy</div>
        </div>
    </div>
</div>

<?php if ($remaining > 0): ?>
<script>
(function() {
    var total = <?= $remaining ?>;
    var display = document.getElementById('timerDisplay');
    function tick() {
        if (total <= 0) { location.reload(); return; }
        total--;
        var m = Math.floor(total / 60);
        var s = total % 60;
        display.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        setTimeout(tick, 1000);
    }
    tick();
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
