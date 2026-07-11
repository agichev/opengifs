<?php
/**
 * OpenGifs Parser — CLI
 *
 * Fetches GIFs from GIPHY & Pixabay, uploads to ImgBB, saves to DB.
 *
 * Usage:
 *   php scripts/parser.php [--dry-run] [--source=giphy|pixabay|all] [--count=20]
 *
 * Env vars required:
 *   IMGBB_API_KEY, GIPHY_API_KEY, PIXABAY_API_KEY
 */

require __DIR__ . '/../config.php';

ensureTable();

$dryRun = in_array('--dry-run', $argv ?? []);
$source = 'all';
$count = 12;

foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--source=(.+)$/', $arg, $m)) $source = $m[1];
    if (preg_match('/^--count=(\d+)$/', $arg, $m)) $count = (int)$m[1];
}

$pdo = getDb();
$imgbbKey = env('IMGBB_API_KEY');
$giphyKey = env('GIPHY_API_KEY');
$pixabayKey = env('PIXABAY_API_KEY');

if (!$imgbbKey) { echo "ERROR: IMGBB_API_KEY not set\n"; exit(1); }

function importGif(string $url, string $title, string $keywords, string $sourceName): void
{
    global $pdo, $imgbbKey, $dryRun;

    $check = $pdo->prepare("SELECT id FROM gifs WHERE imgbb_url = ?");
    $check->execute([$url]);
    if ($check->fetch()) { echo "  SKIP: duplicate\n"; return; }

    echo "  Downloading... ";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => true,
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$data) { echo "FAILED (HTTP $code)\n"; return; }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_buffer($finfo, $data);
    finfo_close($finfo);
    if ($mime !== 'image/gif') { echo "SKIP (not GIF: $mime)\n"; return; }

    echo "Uploading to ImgBB... ";
    $ch = curl_init('https://api.imgbb.com/1/upload');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['key' => $imgbbKey, 'image' => base64_encode($data), 'name' => 'opengifs_' . time()],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200) { echo "FAILED (HTTP $http)\n"; return; }

    $up = json_decode($resp, true);
    if (!($up['data']['url'] ?? null)) { echo "FAILED (invalid response)\n"; return; }

    if ($dryRun) { echo "WOULD IMPORT: $title\n"; return; }

    $stmt = $pdo->prepare("INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, proxy_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $title, $keywords, $sourceName . '_' . time() . '.gif',
        $up['data']['url'], $up['data']['delete_url'] ?? null,
        bin2hex(random_bytes(12)), $up['data']['size'] ?? 0, 'image/gif',
    ]);
    echo "DONE (id: {$pdo->lastInsertId()})\n";
    sleep(1);
}

// ─── GIPHY ───────────────────────────────────────────────

function parseGiphy(int $count): void
{
    global $giphyKey;
    if (!$giphyKey) { echo "GIPHY: skipped (no API key)\n"; return; }
    echo "\n=== GIPHY Trending ($count) ===\n";

    $resp = @file_get_contents("https://api.giphy.com/v1/gifs/trending?api_key={$giphyKey}&limit={$count}&rating=g");
    if (!$resp) { echo "  FAILED\n"; return; }

    $data = json_decode($resp, true);
    foreach ($data['data'] ?? [] as $g) {
        $url = $g['images']['original']['url'] ?? null;
        if (!$url) continue;
        $tags = is_array($g['tags'] ?? null) ? implode(', ', array_slice($g['tags'], 0, 6)) : 'trending';
        importGif($url, $g['title'] ?: 'GIPHY GIF', 'giphy, ' . $tags, 'giphy');
    }
}

// ─── PIXABAY ─────────────────────────────────────────────

function parsePixabay(int $count): void
{
    global $pixabayKey;
    if (!$pixabayKey) { echo "Pixabay: skipped (no API key)\n"; return; }
    echo "\n=== Pixabay Popular ($count) ===\n";

    $queries = ['funny', 'cat', 'dance', 'animals', 'reaction', 'happy', 'celebration', 'love', 'fail', 'sport', 'cute', 'party'];
    $imported = 0;

    foreach ($queries as $q) {
        if ($imported >= $count) break;

        $resp = @file_get_contents("https://pixabay.com/api/?key={$pixabayKey}&q=" . urlencode($q) . "&per_page=30&safesearch=true&order=popular");
        if (!$resp) continue;

        $data = json_decode($resp, true);
        foreach ($data['hits'] ?? [] as $img) {
            if ($imported >= $count) break;

            $url = $img['largeImageURL'] ?: $img['webformatURL'];
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            if ($ext !== 'gif') continue;

            $tags = $img['tags'] ?? $q;
            importGif($url, $img['user'] . ' — ' . $q, 'pixabay, ' . str_replace(', ', ', ', $tags), 'pixabay');
            $imported++;
        }
    }
}

// ─── MAIN ────────────────────────────────────────────────

echo "OpenGifs Parser\n";
echo "Source: $source | Count: $count | Dry run: " . ($dryRun ? 'yes' : 'no') . "\n";

match ($source) {
    'giphy' => parseGiphy($count),
    'pixabay' => parsePixabay($count),
    default => function () use ($count) { parseGiphy($count); parsePixabay($count); },
};

echo "\nDone.\n";
