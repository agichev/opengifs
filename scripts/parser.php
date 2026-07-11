<?php
/**
 * OpenGifs Parser — CLI
 *
 * Sources:
 *   Pixabay (5000 req/h) — real GIFs via q=<keyword>+gif&image_type=all
 *   GIPHY   (100 req/h)  — trending + search with random offset
 *
 * Usage: php scripts/parser.php [--dry-run] [--count=20]
 * Env:   IMGBB_API_KEY, GIPHY_API_KEY, PIXABAY_API_KEY
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

function importGif(string $url, string $title, string $keywords, string $source): bool
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
    if ($http !== 200) { echo "FAILED (HTTP $http)\n"; return false; }

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

// ─── PIXABAY ─────────────────────────────────────────────

echo "\n=== Pixabay GIFs ===\n";
$pixCount = 0;

if ($pixabayKey) {
    $queries = ['funny gif', 'cat gif', 'dance gif', 'animals gif', 'reaction gif', 'happy gif',
                'baby gif', 'dog gif', 'love gif', 'party gif', 'celebration gif', 'sport gif',
                'music gif', 'fail gif', 'cute gif', 'thank you gif', 'wow gif', 'omg gif'];
    shuffle($queries);

    foreach ($queries as $q) {
        if ($pixCount >= $count) break;
        echo "  [{$q}] ";
        $resp = @file_get_contents("https://pixabay.com/api/?key={$pixabayKey}&q=" . urlencode($q) . "&image_type=all&per_page=50&safesearch=true&order=popular");
        if (!$resp) { echo "FAILED\n"; continue; }
        $data = json_decode($resp, true);
        $found = 0;
        foreach ($data['hits'] ?? [] as $img) {
            if ($pixCount >= $count) break;
            $url = $img['webformatURL'] ?? '';
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            if ($ext !== 'gif') continue;
            $tags = $img['tags'] ?? $q;
            if (importGif($url, ($img['user'] ?? 'Pixabay') . ' — ' . str_replace(' gif', '', $q),
                          'pixabay, ' . str_replace(', ', ', ', $tags), 'pixabay')) {
                $pixCount++;
                $found++;
                sleep(1);
            }
        }
        echo "$found imported\n";
    }
    echo "  Pixabay total: $pixCount\n";
} else {
    echo "  Skipped (no API key)\n";
}

// ─── GIPHY ───────────────────────────────────────────────

echo "\n=== GIPHY ===\n";
$giphyCount = 0;

if (!$giphyKey) { echo "  ERROR: GIPHY_API_KEY not set\n"; exit(1); }

// Trending
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

// Search
$searches = ['cat', 'dance', 'funny', 'happy', 'reaction', 'fail', 'cute', 'love', 'baby', 'dog'];
shuffle($searches);
$searchCnt = 0;

foreach (array_unique($searches) as $q) {
    if ($giphyCount >= $count) break;
    if ($searchCnt >= 3) break;
    echo "  [{$q}] ";
    $resp = @file_get_contents("https://api.giphy.com/v1/gifs/search?api_key={$giphyKey}&q=" . urlencode($q) . "&limit=" . ($count - $giphyCount) . "&rating=g&offset=" . rand(0, 30));
    if (!$resp) { echo "FAILED\n"; continue; }
    $data = json_decode($resp, true);
    $found = 0;
    foreach ($data['data'] ?? [] as $g) {
        if ($giphyCount >= $count) break;
        $url = $g['images']['original']['url'] ?? null;
        if (!$url) continue;
        if (importGif($url, $g['title'] ?: $q, 'giphy, ' . $q . ($g['slug'] ? ', ' . $g['slug'] : ''), 'giphy')) {
            $giphyCount++;
            $found++;
            sleep(1);
        }
    }
    echo "$found imported\n";
    $searchCnt++;
}

echo "\nDone. Pixabay: $pixCount | GIPHY: $giphyCount | Total: " . ($pixCount + $giphyCount) . "\n";
