<?php

function autoPopulate(int $count = 6, bool $force = false): void
{
    $pdo = getDb();
    $imgbbKey = env('IMGBB_API_KEY');
    $giphyKey = env('GIPHY_API_KEY');
    $klipyKey = env('KLIPY_API_KEY');

    if (!$imgbbKey) return;

    if (!$force) {
        $total = $pdo->query("SELECT COUNT(*) FROM gifs")->fetchColumn();
        if ($total >= 10) return;
    }

    $sources = [];

    // ── 1. KLIPY ──
    if ($klipyKey) {
        $queries = ['funny', 'cat', 'dance', 'animals', 'reaction', 'happy', 'love', 'fail', 'cute', 'baby', 'dog', 'party', 'celebration', 'sport', 'music'];
        shuffle($queries);
        $used = 0;

        foreach ($queries as $q) {
            if ($used >= 3) break;

            $ch = curl_init("https://api.klipy.com/api/v1/search?query=" . urlencode($q) . "&page=1&per_page=20");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => ['Origin: https://klipy.co', 'Referer: https://klipy.co/'],
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code !== 200 || !$resp) continue;
            $data = json_decode($resp, true);
            if (empty($data['data'])) continue;
            $used++;

            foreach ($data['data'] as $item) {
                $gifUrl = $item['media']['gif']['url'] ?? $item['media']['mp4']['url'] ?? null;
                if (!$gifUrl) continue;

                $tags = is_array($item['tags'] ?? null) ? implode(', ', array_slice($item['tags'], 0, 6)) : $q;
                $sources[] = [
                    'url' => $gifUrl,
                    'type' => 'klipy',
                    'title' => $item['title'] ?? $q,
                    'keywords' => 'klipy, ' . $tags,
                ];
            }
        }
    }

    // ── 2. GIPHY (trending only, 100 req/h) ──
    if ($giphyKey) {
        $resp = @file_get_contents("https://api.giphy.com/v1/gifs/trending?api_key={$giphyKey}&limit={$count}&rating=g");
        if ($resp) {
            $data = json_decode($resp, true);
            foreach ($data['data'] ?? [] as $g) {
                $gifUrl = $g['images']['original']['url'] ?? null;
                if (!$gifUrl) continue;
                $sources[] = [
                    'url' => $gifUrl, 'type' => 'giphy',
                    'title' => $g['title'] ?: 'GIPHY GIF',
                    'keywords' => 'giphy' . ($g['slug'] ? ', ' . $g['slug'] : ''),
                ];
            }
        }
    }

    // ── IMPORT ──
    $imported = 0;
    shuffle($sources);

    foreach ($sources as $src) {
        if ($imported >= $count) break;

        $check = $pdo->prepare("SELECT id FROM gifs WHERE source_url = ?");
        $check->execute([$src['url']]);
        if ($check->fetch()) continue;

        $ch = curl_init($src['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
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
            CURLOPT_POSTFIELDS => ['key' => $imgbbKey, 'image' => $imageData, 'name' => 'og_' . time()],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) continue;

        $up = json_decode($resp, true);
        $imgbbUrl = $up['data']['display_url'] ?? $up['data']['url'] ?? null;
        if (!$imgbbUrl) continue;

        $stmt = $pdo->prepare("INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, source_url, proxy_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            mb_substr($src['title'], 0, 255),
            mb_substr($src['keywords'], 0, 500),
            'auto_' . time() . '.gif',
            $imgbbUrl,
            $up['data']['delete_url'] ?? null,
            $src['url'],
            bin2hex(random_bytes(12)),
            $up['data']['size'] ?? 0,
            'image/gif',
        ]);
        $imported++;
        sleep(1);
    }
}
