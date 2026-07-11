<?php

function importFromUrl(string $url, string $title, string $keywords): string
{
    $pdo = getDb();
    $imgbbKey = env('IMGBB_API_KEY');

    if (!$imgbbKey) {
        return 'IMGBB_API_KEY is not configured.';
    }

    $url = trim($url);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return 'Invalid URL.';
    }

    // Check duplicate
    $check = $pdo->prepare("SELECT id, proxy_path FROM gifs WHERE source_url = ?");
    $check->execute([$url]);
    $existing = $check->fetch();
    if ($existing) {
        return 'Already exists: <a href="/gif/' . htmlspecialchars($existing['proxy_path']) . '">view GIF</a>.';
    }

    // Download
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($code !== 200 || !$data) {
        return 'Failed to download: HTTP ' . $code . ($error ? ' (' . $error . ')' : '');
    }

    // Validate GIF
    if (substr($data, 0, 6) !== 'GIF87a' && substr($data, 0, 6) !== 'GIF89a') {
        return 'Not a valid GIF file.';
    }

    // Upload to ImgBB
    $imageData = base64_encode($data);
    $ch = curl_init('https://api.imgbb.com/1/upload');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['key' => $imgbbKey, 'image' => $imageData, 'name' => 'og_' . time()],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return 'Failed to upload to image host.';
    }

    $up = json_decode($resp, true);
    $imgbbUrl = $up['data']['display_url'] ?? $up['data']['url'] ?? null;
    if (!$imgbbUrl) {
        return 'Invalid response from image host.';
    }

    $proxyPath = bin2hex(random_bytes(12));
    $stmt = $pdo->prepare("INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, source_url, proxy_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        mb_substr($title ?: 'Imported GIF', 0, 255),
        mb_substr($keywords ?: 'imported', 0, 500),
        'import_' . time() . '.gif',
        $imgbbUrl,
        $up['data']['delete_url'] ?? null,
        $url,
        $proxyPath,
        $up['data']['size'] ?? 0,
        'image/gif',
    ]);

    return 'SUCCESS:' . $proxyPath;
}
