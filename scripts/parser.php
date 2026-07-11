<?php
/**
 * OpenGifs Parser — CLI
 *
 * Sources:
 *   Klipy  — real GIFs (unlimited)
 *   GIPHY  — trending (100 req/h)
 *
 * Usage: php scripts/parser.php [--dry-run] [--count=20]
 * Env:   IMGBB_API_KEY, GIPHY_API_KEY
 */

require __DIR__ . '/../config.php';

ensureTable();

$dryRun = in_array('--dry-run', $argv ?? []);
$count = 20;
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--count=(\d+)$/', $arg, $m)) $count = (int)$m[1];
}

$pdo = getDb();
$imgbbKey = env('IMGBB_API_KEY');
$giphyKey = env('GIPHY_API_KEY');

if (!$imgbbKey) { echo "ERROR: IMGBB_API_KEY not set\n"; exit(1); }

function importGif(string $url, string $title, string $keywords, string $source): bool
{
    global $pdo, $imgbbKey, $dryRun;

    $check = $pdo->prepare("SELECT id FROM gifs WHERE source_url = ?");
    $check->execute([$url]);
    if ($check->fetch()) { echo "  SKIP: duplicate\n"; return false; }

    echo "  Downloading... ";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => true]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$data) { echo "FAILED (HTTP $code)\n"; return false; }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_buffer($finfo, $data);
    finfo_close($finfo);
    if ($mime !== 'image/gif') { echo "SKIP (not GIF: $mime)\n"; return false; }

    echo "ImgBB... ";
    $ch = curl_init('https://api.imgbb.com/1/upload');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['key' => $imgbbKey, 'image' => base64_encode($data), 'name' => 'og_' . time()],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200) { echo "FAILED (HTTP $http)\n"; return false; }

    $up = json_decode($resp, true);
    $imgbbUrl = $up['data']['display_url'] ?? $up['data']['url'] ?? null;
    if (!$imgbbUrl) { echo "FAILED (bad response)\n"; return false; }

    if ($dryRun) { echo "WOULD IMPORT: $title\n"; return true; }

    $stmt = $pdo->prepare("INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, source_url, proxy_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        mb_substr($title, 0, 255), mb_substr($keywords, 0, 500),
        'auto_' . time() . '.gif',
        $imgbbUrl, $up['data']['delete_url'] ?? null,
        $url,
        bin2hex(random_bytes(12)), $up['data']['size'] ?? 0, 'image/gif',
    ]);
    echo "DONE (#{$pdo->lastInsertId()})\n";
    return true;
}

// ─── KLIPY (no key needed) ──────────────────────────────

echo "\n=== Klipy ===\n";
$klipyCount = 0;

shuffle($queries);

foreach ($queries as $q) {
    if ($klipyCount >= $count) break;
    echo "  [{$q}] ";

    $ch = curl_init("https://api.klipy.com/api/v1/search?query=" . urlencode($q) . "&page=1&per_page=30");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Origin: https://klipy.co', 'Referer: https://klipy.co/'],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$resp) { echo "FAILED\n"; continue; }

    $data = json_decode($resp, true);
    $found = 0;
    foreach ($data['data'] ?? [] as $item) {
        if ($klipyCount >= $count) break;
        $gifUrl = $item['media']['gif']['url'] ?? $item['media']['mp4']['url'] ?? null;
        if (!$gifUrl) continue;
        $tags = is_array($item['tags'] ?? null) ? implode(', ', array_slice($item['tags'], 0, 6)) : $q;
        if (importGif($gifUrl, $item['title'] ?? $q, 'klipy, ' . $tags, 'klipy')) {
            $klipyCount++;
            $found++;
            sleep(1);
        }
    }
    echo "$found imported\n";
}
echo "  Klipy total: $klipyCount\n";

// ─── GIPHY ───────────────────────────────────────────────

echo "\n=== GIPHY ===\n";
$giphyCount = 0;

if (!$giphyKey) { echo "  Skipped (no API key)\n"; }

if ($giphyKey) {
    echo "  [trending] ";
    $resp = @file_get_contents("https://api.giphy.com/v1/gifs/trending?api_key={$giphyKey}&limit={$count}&rating=g");
    if ($resp) {
        $data = json_decode($resp, true);
        foreach ($data['data'] ?? [] as $g) {
            $url = $g['images']['original']['url'] ?? null;
            if (!$url) continue;
            if (importGif($url, $g['title'] ?: 'GIPHY GIF', 'giphy' . ($g['slug'] ? ', ' . $g['slug'] : ''), 'giphy')) {
                $giphyCount++;
                sleep(1);
            }
        }
        echo "$giphyCount imported\n";
    }
}

echo "\nDone. Klipy: $klipyCount | GIPHY: $giphyCount | Total: " . ($klipyCount + $giphyCount) . "\n";
