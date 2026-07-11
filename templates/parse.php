<?php
$pageTitle = 'Import GIFs';
$hideSearch = true;
require __DIR__ . '/header.php';
?>

<h1 class="page-title">Import GIFs</h1>

<div style="background:#fff;border:1px solid #d0d8e8;border-radius:8px;padding:30px;box-shadow:0 1px 4px rgba(0,0,0,0.08);max-width:600px;margin:0 auto;">
    <p style="font-size:14px;color:#555;margin-bottom:20px;line-height:1.7;">
        Automatically fetch trending GIFs from <strong>GIPHY</strong> and <strong>Pixabay</strong>.
        New GIFs are uploaded to ImgBB and added to the library.
    </p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
        <div style="background:#e8f0fe;border-radius:6px;padding:16px;text-align:center;">
            <div style="font-size:28px;font-weight:bold;color:#2a5f9e;"><?= (int)$totalGifs ?></div>
            <div style="font-size:12px;color:#666;">Total GIFs</div>
        </div>
        <div style="background:#e8f0fe;border-radius:6px;padding:16px;text-align:center;">
            <div style="font-size:28px;font-weight:bold;color:#2a5f9e;"><?= (int)$giphyCount ?></div>
            <div style="font-size:12px;color:#666;">From GIPHY</div>
        </div>
        <div style="background:#e8f0fe;border-radius:6px;padding:16px;text-align:center;">
            <div style="font-size:28px;font-weight:bold;color:#2a5f9e;"><?= (int)$pixCount ?></div>
            <div style="font-size:12px;color:#666;">From Pixabay</div>
        </div>
        <div style="background:#e8f0fe;border-radius:6px;padding:16px;text-align:center;">
            <div style="font-size:28px;font-weight:bold;color:#2a5f9e;" id="parseTimer">
                <?= $remaining > 0 ? ceil($remaining / 60) . 'm' : 'Ready' ?>
            </div>
            <div style="font-size:12px;color:#666;">Next parse in</div>
        </div>
    </div>

    <button id="parseBtn" style="display:block;width:100%;padding:14px;background:linear-gradient(to bottom,#4a90d9,#357abd);border:2px solid #2a5f9e;border-radius:6px;color:#fff;font-size:16px;font-weight:bold;cursor:pointer;text-shadow:1px 1px 0 #2a5f9e;<?= $remaining > 0 ? 'opacity:0.5;cursor:not-allowed;' : '' ?>" <?= $remaining > 0 ? 'disabled' : '' ?>>
        <?= $remaining > 0 ? 'Wait ' . ceil($remaining / 60) . ' min' : 'Import GIFs Now' ?>
    </button>

    <div id="parseResult" style="margin-top:16px;padding:12px;border-radius:6px;font-size:14px;display:none;"></div>

    <p style="margin-top:16px;font-size:12px;color:#888;text-align:center;">
        GIPHY: 100 req/h · Pixabay: 5000 req/h · Cooldown: 10 min
    </p>
</div>

<script>
(function() {
    var btn = document.getElementById('parseBtn');
    var result = document.getElementById('parseResult');
    var timer = document.getElementById('parseTimer');

    btn.addEventListener('click', function() {
        if (btn.disabled) return;

        btn.disabled = true;
        btn.textContent = 'Importing...';
        btn.style.opacity = '0.5';
        result.style.display = 'none';

        fetch('/parse')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                result.style.display = 'block';
                if (d.success) {
                    result.className = 'flash-success';
                    result.textContent = 'Imported! Total: ' + d.total_gifs + ' GIFs (took ' + d.time + ')';
                    btn.textContent = 'Import GIFs Now';
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                    // Start countdown
                    startCountdown(600);
                } else if (d.error === 'Cooldown') {
                    result.className = 'flash-error';
                    result.textContent = 'Please wait ' + Math.ceil(d.remaining / 60) + ' minutes.';
                    btn.textContent = 'Wait ' + Math.ceil(d.remaining / 60) + ' min';
                    startCountdown(d.remaining);
                } else {
                    result.className = 'flash-error';
                    result.textContent = 'Error: ' + (d.error || 'Unknown');
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.textContent = 'Try Again';
                }
            })
            .catch(function(err) {
                result.style.display = 'block';
                result.className = 'flash-error';
                result.textContent = 'Error: ' + err.message;
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.textContent = 'Try Again';
            });
    });

    function startCountdown(seconds) {
        if (seconds <= 0) {
            timer.textContent = 'Ready';
            btn.textContent = 'Import GIFs Now';
            btn.disabled = false;
            btn.style.opacity = '1';
            return;
        }
        var min = Math.ceil(seconds / 60);
        timer.textContent = min + 'm';
        btn.textContent = 'Wait ' + min + ' min';
        setTimeout(function() { startCountdown(seconds - 1); }, 1000);
    }

    // Start timer if cooldown active
    var remaining = <?= $remaining > 0 ? $remaining : 0 ?>;
    if (remaining > 0) startCountdown(remaining);
})();
</script>

<?php require __DIR__ . '/footer.php'; ?>
