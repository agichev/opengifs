<?php

function autoPopulate(int $count = 6): void
{
    $pdo = getDb();
    $apiKey = env('IMGBB_API_KEY');

    if (!$apiKey) return;

    $total = $pdo->query("SELECT COUNT(*) FROM gifs")->fetchColumn();
    if ($total >= 10) return;

    $sources = [];

    // GIPHY trending
    $resp = @file_get_contents('https://api.giphy.com/v1/gifs/trending?limit=' . $count . '&rating=g');
    if ($resp) {
        $data = json_decode($resp, true);
        foreach ($data['data'] ?? [] as $g) {
            $url = $g['images']['original']['url'] ?? null;
            if ($url) $sources[] = ['url' => $url, 'title' => $g['title'] ?: 'GIPHY GIF', 'keywords' => 'giphy, trending, ' . ($g['slug'] ?? '')];
        }
    }

    // Tenor featured
    $resp = @file_get_contents('https://tenor.googleapis.com/v2/featured?key=LIVDSRZULELA&limit=' . $count . '&contentfilter=high');
    if ($resp) {
        $data = json_decode($resp, true);
        foreach ($data['results'] ?? [] as $g) {
            $url = $g['media_formats']['gif']['url'] ?? $g['media'][0]['gif']['url'] ?? null;
            if ($url) $sources[] = ['url' => $url, 'title' => $g['title'] ?: $g['content_description'] ?: 'Tenor GIF', 'keywords' => 'tenor, ' . implode(', ', array_slice($g['tags'] ?? [], 0, 5))];
        }
    }

    $imported = 0;
    foreach ($sources as $src) {
        if ($imported >= $count) break;

        $check = $pdo->prepare("SELECT id FROM gifs WHERE imgbb_url = ?");
        $check->execute([$src['url']]);
        if ($check->fetch()) continue;

        $ch = curl_init($src['url']);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => true]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$data) continue;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $data);
        finfo_close($finfo);
        if ($mime !== 'image/gif') continue;

        $imageData = base64_encode($data);
        $ch = curl_init('https://api.imgbb.com/1/upload');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['key' => $apiKey, 'image' => $imageData, 'name' => 'opengifs_auto'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) continue;
        $up = json_decode($resp, true);
        if (!($up['data']['url'] ?? null)) continue;

        $stmt = $pdo->prepare("INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, proxy_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $src['title'],
            $src['keywords'],
            'auto_' . time() . '.gif',
            $up['data']['url'],
            $up['data']['delete_url'] ?? null,
            bin2hex(random_bytes(12)),
            $up['data']['size'] ?? 0,
            'image/gif',
        ]);
        $imported++;
        sleep(1);
    }
}
