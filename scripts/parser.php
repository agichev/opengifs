<?php
/**
 * OpenGifs Parser
 *
 * Fetches trending GIFs from GIPHY, Tenor, Reddit and imports them.
 * Usage: php scripts/parser.php [--dry-run] [--source=giphy|tenor|reddit|all]
 */

require __DIR__ . '/../config.php';

// Ensure table exists
ensureTable();

$dryRun = in_array('--dry-run', $argv ?? []);
$source = 'all';
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--source=(.+)$/', $arg, $m)) {
        $source = $m[1];
    }
}

$pdo = getDb();

function importGif(string $url, string $title, string $keywords, string $source): void
{
    global $pdo, $dryRun;

    $apiKey = env('IMGBB_API_KEY');
    if (!$apiKey) {
        echo "  SKIP: IMGBB_API_KEY not set\n";
        return;
    }

    // Check for duplicates
    $check = $pdo->prepare("SELECT id FROM gifs WHERE imgbb_url = ? OR (title = ? AND keywords = ?)");
    $check->execute([$url, $title, $keywords]);
    if ($check->fetch()) {
        echo "  SKIP: already exists\n";
        return;
    }

    echo "  Downloading... ";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$data) {
        echo "FAILED (HTTP $httpCode)\n";
        return;
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'gif_');
    file_put_contents($tmpFile, $data);

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpFile);
    finfo_close($finfo);

    if ($mime !== 'image/gif') {
        echo "SKIP (not GIF: $mime)\n";
        unlink($tmpFile);
        return;
    }

    echo "Uploading to ImgBB... ";
    $imageData = base64_encode($data);

    $ch = curl_init('https://api.imgbb.com/1/upload');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'key' => $apiKey,
            'image' => $imageData,
            'name' => 'opengifs_' . time(),
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    unlink($tmpFile);

    if ($httpCode !== 200) {
        echo "FAILED\n";
        return;
    }

    $data = json_decode($response, true);
    if (!($data['data']['url'] ?? null)) {
        echo "FAILED (invalid response)\n";
        return;
    }

    $proxyPath = bin2hex(random_bytes(12));
    $size = $data['data']['size'] ?? strlen($data);

    if ($dryRun) {
        echo "WOULD IMPORT: $title\n";
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, proxy_path, file_size, mime_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $title,
        $keywords,
        $source . '_' . time() . '.gif',
        $data['data']['url'],
        $data['data']['delete_url'] ?? null,
        $proxyPath,
        $size,
        'image/gif',
    ]);

    echo "DONE (id: {$pdo->lastInsertId()})\n";
}

// ─── GIPHY ───────────────────────────────────────────────

function parseGiphy(): void
{
    echo "\n=== GIPHY Trending ===\n";
    $url = 'https://api.giphy.com/v1/gifs/trending?limit=12&rating=g';
    $response = @file_get_contents($url);
    if (!$response) {
        echo "  FAILED to fetch from GIPHY\n";
        return;
    }
    $data = json_decode($response, true);
    foreach ($data['data'] ?? [] as $gif) {
        $gifUrl = $gif['images']['original']['url'] ?? null;
        if (!$gifUrl) continue;
        $title = $gif['title'] ?: 'GIPHY GIF';
        $keywords = implode(', ', array_slice($gif['tags'] ?? $gif['slug'] ? [$gif['slug']] : [], 0, 8));
        importGif($gifUrl, $title, $keywords, 'giphy');
        sleep(1); // rate limit
    }
}

// ─── TENOR ───────────────────────────────────────────────

function parseTenor(): void
{
    echo "\n=== Tenor Trending ===\n";
    $anonKey = 'LIVDSRZULELA';
    $url = "https://tenor.googleapis.com/v2/featured?key={$anonKey}&limit=12&contentfilter=high";
    $response = @file_get_contents($url);
    if (!$response) {
        echo "  FAILED to fetch from Tenor\n";
        return;
    }
    $data = json_decode($response, true);
    foreach ($data['results'] ?? [] as $gif) {
        $gifUrl = $gif['media_formats']['gif']['url'] ?? $gif['media'][0]['gif']['url'] ?? null;
        if (!$gifUrl) continue;
        $title = $gif['title'] ?: $gif['content_description'] ?: 'Tenor GIF';
        $keywords = implode(', ', array_slice($gif['tags'] ?? [], 0, 8));
        importGif($gifUrl, $title, $keywords, 'tenor');
        sleep(1);
    }
}

// ─── REDDIT ───────────────────────────────────────────────

function parseReddit(): void
{
    echo "\n=== Reddit r/gifs ===\n";
    $url = 'https://www.reddit.com/r/gifs/hot.json?limit=12';
    $response = @file_get_contents($url, false, stream_context_create([
        'http' => ['header' => "User-Agent: OpenGifs/1.0\n"],
    ]));
    if (!$response) {
        echo "  FAILED to fetch from Reddit\n";
        return;
    }
    $data = json_decode($response, true);
    foreach ($data['data']['children'] ?? [] as $child) {
        $post = $child['data'] ?? [];
        $gifUrl = $post['url'] ?? null;
        if (!$gifUrl || !str_ends_with($gifUrl, '.gif')) continue;
        $title = $post['title'] ?: 'Reddit GIF';
        $keywords = 'reddit, ' . ($post['link_flair_text'] ?? 'gif');
        importGif($gifUrl, $title, $keywords, 'reddit');
        sleep(1);
    }
}

// ─── MAIN ────────────────────────────────────────────────

echo "OpenGifs Parser\n";
echo "Source: $source\n";
echo "Dry run: " . ($dryRun ? 'yes' : 'no') . "\n";

match ($source) {
    'giphy' => parseGiphy(),
    'tenor' => parseTenor(),
    'reddit' => parseReddit(),
    default => function () {
        parseGiphy();
        parseTenor();
        parseReddit();
    },
};

echo "\nDone.\n";
