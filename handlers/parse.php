<?php

function autoPopulate(int $count = 6): void
{
    $pdo = getDb();
    $imgbbKey = env('IMGBB_API_KEY');
    $giphyKey = env('GIPHY_API_KEY');
    $pixabayKey = env('PIXABAY_API_KEY');

    if (!$imgbbKey) return;

    $total = $pdo->query("SELECT COUNT(*) FROM gifs")->fetchColumn();
    if ($total >= 10) return;

    $sources = [];

    // ── GIPHY (100 req/h, use limit=50 → 50 GIFs per call) ──
    if ($giphyKey) {
        $resp = @file_get_contents("https://api.giphy.com/v1/gifs/trending?api_key={$giphyKey}&limit={$count}&rating=g");
        if ($resp) {
            $data = json_decode($resp, true);
            foreach ($data['data'] ?? [] as $g) {
                $url = $g['images']['original']['url'] ?? null;
                if ($url) {
                    $tags = is_array($g['tags'] ?? null) ? $g['tags'] : [];
                    $slug = $g['slug'] ?? '';
                    $keywords = 'giphy' . ($tags ? ', ' . implode(', ', array_slice($tags, 0, 6)) : '') . ($slug ? ', ' . $slug : '');
                    $sources[] = ['url' => $url, 'title' => $g['title'] ?: 'GIF', 'keywords' => $keywords];
                }
            }
        }
    }

    // ── PIXABAY (5000 req/h) ──
    if ($pixabayKey) {
        $pixabayQueries = ['funny', 'cat', 'dance', 'fail', 'animals', 'sport', 'celebration', 'reaction', 'happy', 'love'];
        shuffle($pixabayQueries);
        $used = 0;

        foreach ($pixabayQueries as $q) {
            if ($used >= 3) break;

            $resp = @file_get_contents("https://pixabay.com/api/?key={$pixabayKey}&q=" . urlencode($q) . "&per_page=20&safesearch=true&order=popular");
            if (!$resp) continue;

            $data = json_decode($resp, true);
            $hits = $data['hits'] ?? [];

            if (!count($hits)) continue;
            $used++;

            foreach ($hits as $img) {
                $url = $img['webformatURL'] ?? '';
                $largeUrl = $img['largeImageURL'] ?? '';
                $type = $img['type'] ?? '';

                // Try the largeImageURL first (higher quality, sometimes different format)
                $finalUrl = $largeUrl ?: $url;
                $ext = strtolower(pathinfo(parse_url($finalUrl, PHP_URL_PATH), PATHINFO_EXTENSION));

                // Only accept actual GIFs or unknown extensions (we'll validate by MIME)
                if ($ext === 'gif') {
                    $tags = $img['tags'] ?? $q;
                    $sources[] = [
                        'url' => $finalUrl,
                        'title' => $img['user'] ? $img['user'] . ' — ' . $q : 'Pixabay ' . $q,
                        'keywords' => 'pixabay, ' . str_replace(', ', ', ', $tags),
                    ];
                }
            }
        }
    }

    // ── IMPORT ──
    $imported = 0;
    shuffle($sources);

    foreach ($sources as $src) {
        if ($imported >= $count) break;

        $check = $pdo->prepare("SELECT id FROM gifs WHERE imgbb_url = ?");
        $check->execute([$src['url']]);
        if ($check->fetch()) continue;

        $ch = curl_init($src['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
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
            CURLOPT_POSTFIELDS => ['key' => $imgbbKey, 'image' => $imageData, 'name' => 'opengifs_auto'],
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
