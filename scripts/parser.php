<?php
/**
 * OpenGifs Parser — CLI
 *
 * Uses Pixabay (5000 req/h) to discover trending topics,
 * then fetches actual GIFs from GIPHY (100 req/h).
 *
 * Usage:
 *   php scripts/parser.php [--dry-run] [--count=20]
 *
 * Env: IMGBB_API_KEY, GIPHY_API_KEY, PIXABAY_API_KEY
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
$pixabayKey = env('PIXABAY_API_KEY');

if (!$imgbbKey) { echo "ERROR: IMGBB_API_KEY not set\n"; exit(1); }

function importGif(string $url, string $title, string $keywords): bool
{
    global $pdo, $imgbbKey, $dryRun;

    $check = $pdo->prepare("SELECT id FROM gifs WHERE source_url = ?");
    $check->execute([$url]);
    if ($check->fetch()) { echo "  SKIP: duplicate\n"; return false; }

    echo "  Downloading... ";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => true]);
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
        CURLOPT_POSTFIELDS => ['key' => $imgbbKey, 'image' => base64_encode($data), 'name' => 'opengifs_' . time()],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200) { echo "FAILED\n"; return false; }

    $up = json_decode($resp, true);
    if (!($up['data']['url'] ?? null)) { echo "FAILED (bad response)\n"; return false; }

    if ($dryRun) { echo "WOULD IMPORT: $title\n"; return true; }

    $stmt = $pdo->prepare("INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, source_url, proxy_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        mb_substr($title, 0, 255), mb_substr($keywords, 0, 500),
        'auto_' . time() . '.gif',
        $up['data']['url'], $up['data']['delete_url'] ?? null,
        $url,
        bin2hex(random_bytes(12)), $up['data']['size'] ?? 0, 'image/gif',
    ]);
    echo "DONE (#{$pdo->lastInsertId()})\n";
    return true;
}

// ─── Step 1: Pixabay → discover topics ──────────────────

echo "\n=== Pixabay: discovering topics ===\n";
$topics = [];

if ($pixabayKey) {
    // Editors choice (trending)
    $resp = @file_get_contents("https://pixabay.com/api/?key={$pixabayKey}&per_page=50&safesearch=true&order=popular&editors_choice=true");
    if ($resp) {
        $data = json_decode($resp, true);
        foreach ($data['hits'] ?? [] as $img) {
            foreach (explode(', ', $img['tags'] ?? '') as $t) {
                $t = trim($t);
                if (strlen($t) > 2 && strlen($t) < 30) $topics[$t] = ($topics[$t] ?? 0) + 1;
            }
        }
    }

    // Category-specific
    foreach (['animals', 'sports', 'music', 'food', 'nature', 'people', 'feelings', 'travel'] as $cat) {
        $resp = @file_get_contents("https://pixabay.com/api/?key={$pixabayKey}&category={$cat}&per_page=20&safesearch=true&order=popular");
        if (!$resp) continue;
        $data = json_decode($resp, true);
        foreach ($data['hits'] ?? [] as $img) {
            foreach (explode(', ', $img['tags'] ?? '') as $t) {
                $t = trim($t);
                if (strlen($t) > 2 && strlen($t) < 30) $topics[$t] = ($topics[$t] ?? 0) + 1;
            }
        }
    }

    echo "  Found " . count($topics) . " unique topics\n";
} else {
    // Fallback topics if no Pixabay key
    $topics = ['funny', 'cat', 'dance', 'animals', 'reaction', 'happy', 'fail', 'celebration'];
    foreach ($topics as $t) $topics[$t] = 1;
}

// ─── Step 2: GIPHY → fetch GIFs by topic ────────────────

echo "\n=== GIPHY: fetching GIFs ===\n";
$imported = 0;

if (!$giphyKey) {
    echo "  ERROR: GIPHY_API_KEY not set\n";
    exit(1);
}

// Always fetch trending first
echo "  [trending] ";
$resp = @file_get_contents("https://api.giphy.com/v1/gifs/trending?api_key={$giphyKey}&limit={$count}&rating=g");
if ($resp) {
    $data = json_decode($resp, true);
    $gifCount = 0;
    foreach ($data['data'] ?? [] as $g) {
        $url = $g['images']['original']['url'] ?? null;
        if (!$url) continue;
        $slug = $g['slug'] ?? '';
        if (importGif($url, $g['title'] ?: 'GIPHY GIF', 'giphy, ' . $slug)) {
            $imported++; $gifCount++;
            sleep(1);
        }
    }
    echo "$gifCount imported\n";
} else {
    echo "FAILED\n";
}

// Search by topics
arsort($topics);
$searched = 0;
foreach (array_keys($topics) as $topic) {
    if ($imported >= $count) break;
    if ($searched >= 5) break;

    echo "  [{$topic}] ";
    $resp = @file_get_contents("https://api.giphy.com/v1/gifs/search?api_key={$giphyKey}&q=" . urlencode($topic) . "&limit={$count}&rating=g");
    if (!$resp) { echo "FAILED\n"; continue; }

    $data = json_decode($resp, true);
    $gifCount = 0;
    foreach ($data['data'] ?? [] as $g) {
        if ($imported >= $count) break;
        $url = $g['images']['original']['url'] ?? null;
        if (!$url) continue;
        if (importGif($url, $g['title'] ?: $topic, 'giphy, ' . $topic)) {
            $imported++; $gifCount++;
            sleep(1);
        }
    }
    echo "$gifCount imported\n";
    $searched++;
}

echo "\nDone. Imported: $imported\n";
