<?php
$pageTitle = 'Import GIFs';
$hideSearch = true;
require __DIR__ . '/header.php';
?>

<h1 class="page-title">Import GIFs</h1>

<div style="background:#fff;border:1px solid #d0d8e8;border-radius:8px;padding:30px;box-shadow:0 1px 4px rgba(0,0,0,0.08);max-width:500px;margin:0 auto;text-align:center;">
    <p style="font-size:14px;color:#555;margin-bottom:24px;line-height:1.7;">
        Automatically fetch trending GIFs from GIPHY and Pixabay.<br>
        New GIFs are uploaded to ImgBB and added to the library.
    </p>

    <button id="parseBtn" style="width:100%;padding:16px;background:linear-gradient(to bottom,#4a90d9,#357abd);border:2px solid #2a5f9e;border-radius:8px;color:#fff;font-size:18px;font-weight:bold;cursor:pointer;text-shadow:1px 1px 0 #2a5f9e;position:relative;">
        <span id="parseBtnText"><?= $remaining > 0 ? 'Wait ' . ceil($remaining / 60) . ' min' : 'Import GIFs Now' ?></span>
    </button>

    <div id="parseResult" style="margin-top:16px;min-height:40px;font-size:14px;"></div>

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
            <div style="font-size:22px;font-weight:bold;color:#2a5f9e;"><?= (int)$pixCount ?></div>
            <div style="font-size:11px;color:#888;">Pixabay</div>
        </div>
    </div>
</div>

<style>
#parseBtn.loading { pointer-events:none; opacity:0.7; }
#parseBtn .spinner { display:none; vertical-align:middle; width:20px; height:20px; border:2px solid rgba(255,255,255,0.3); border-top-color:#fff; border-radius:50%; animation:spin 0.7s linear infinite; margin-right:8px; }
#parseBtn.loading .spinner { display:inline-block; }
#parseBtn.loading #parseBtnText { display:none; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
(function() {
    var btn = document.getElementById('parseBtn');
    var btnText = document.getElementById('parseBtnText');
    var result = document.getElementById('parseResult');

    function pad(n) { return n < 10 ? '0' + n : n; }

    function updateTimer(seconds) {
        if (seconds <= 0) {
            btnText.textContent = 'Import GIFs Now';
            btn.disabled = false;
            btn.classList.remove('loading');
            result.innerHTML = '';
            return;
        }
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        btnText.textContent = 'Wait ' + m + ':' + pad(s);
        btn.disabled = true;
        setTimeout(function() { updateTimer(seconds - 1); }, 1000);
    }

    btn.addEventListener('click', function() {
        if (btn.disabled || btn.classList.contains('loading')) return;

        btn.classList.add('loading');
        result.innerHTML = '<span style="color:#888;">Importing GIFs, please wait...</span>';

        fetch('/parse')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btn.classList.remove('loading');
                if (d.success) {
                    result.innerHTML = '<span style="color:#28a745;font-weight:600;">Imported ' + d.imported + ' new GIF' + (d.imported !== 1 ? 's' : '') + ' (total: ' + d.total + ')</span>';
                    updateTimer(600);
                    // Update counters
                    var counters = document.querySelectorAll('[style*="background:#e8f0fe"] div:first-child');
                    if (counters.length > 0) counters[0].textContent = d.total;
                } else if (d.error === 'cooldown') {
                    result.innerHTML = '<span style="color:#dc3545;">Please wait before importing again.</span>';
                    updateTimer(d.remaining);
                } else {
                    result.innerHTML = '<span style="color:#dc3545;">Error: ' + (d.error || 'Unknown') + '</span>';
                    btn.disabled = false;
                }
            })
            .catch(function() {
                btn.classList.remove('loading');
                result.innerHTML = '<span style="color:#dc3545;">Network error. Try again.</span>';
                btn.disabled = false;
            });
    });

    // Start initial timer if cooldown active
    var initial = <?= (int)$remaining ?>;
    if (initial > 0) updateTimer(initial);
})();
</script>

<?php require __DIR__ . '/footer.php'; ?>
