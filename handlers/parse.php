<?php

function autoPopulate(int $count = 6, bool $force = false): void
{
    $pdo = getDb();
    $imgbbKey = env('IMGBB_API_KEY');
    $giphyKey = env('GIPHY_API_KEY');

    if (!$imgbbKey) return;

    if (!$force) {
        $total = $pdo->query("SELECT COUNT(*) FROM gifs")->fetchColumn();
        if ($total >= 10) return;
    }

    $sources = [];

    // ── 1. KLIPY (no key needed, public API) ──
    $klipyQueries = ['funny', 'cat', 'dance', 'animals', 'reaction', 'happy', 'love', 'fail', 'cute', 'baby', 'dog', 'party', 'celebration', 'sport', 'music', 'wow', 'omg', 'thank you', 'yes', 'no', 'sorry', 'please'];
    shuffle($klipyQueries);
    $used = 0;

    foreach ($klipyQueries as $q) {
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
            $title = $item['title'] ?: $q;
            $sources[] = [
                'url' => $gifUrl,
                'type' => 'klipy',
                'title' => $title,
                'keywords' => 'klipy, ' . $tags,
            ];
        }
    }

    // ── 2. GIPHY trending + random search ──
    if ($giphyKey) {
        // Trending (may overlap, but that's fine)
        $resp = @file_get_contents("https://api.giphy.com/v1/gifs/trending?api_key={$giphyKey}&limit={$count}&rating=g");
        if ($resp) {
            $data = json_decode($resp, true);
            foreach ($data['data'] ?? [] as $g) {
                $gifUrl = $g['images']['original']['url'] ?? null;
                if (!$gifUrl) continue;
                $sources[] = [
                    'url' => $gifUrl, 'type' => 'giphy',
                    'title' => $g['title'] ?: 'GIF',
                    'keywords' => 'giphy' . ($g['slug'] ? ', ' . $g['slug'] : ''),
                ];
            }
        }

        // Random search with random offset for variety
        $giphySearches = ['cat', 'dance', 'funny', 'happy', 'reaction', 'fail', 'cute', 'love', 'baby', 'dog', 'party', 'wow', 'omg', 'celebrate', 'sport'];
        shuffle($giphySearches);
        $searched = 0;
        foreach ($giphySearches as $q) {
            if ($searched >= 2) break;
            $off = rand(0, 40);
            $resp = @file_get_contents("https://api.giphy.com/v1/gifs/search?api_key={$giphyKey}&q=" . urlencode($q) . "&limit={$count}&rating=g&offset={$off}");
            if (!$resp) continue;
            $data = json_decode($resp, true);
            foreach ($data['data'] ?? [] as $g) {
                $gifUrl = $g['images']['original']['url'] ?? null;
                if (!$gifUrl) continue;
                $sources[] = [
                    'url' => $gifUrl, 'type' => 'giphy',
                    'title' => $g['title'] ?: $q,
                    'keywords' => 'giphy, ' . $q . ($g['slug'] ? ', ' . $g['slug'] : ''),
                ];
            }
            $searched++;
        }
    }

    // ── IMPORT with content hash dedup ──
    $imported = 0;
    shuffle($sources);

    foreach ($sources as $src) {
        if ($imported >= $count) break;

        // Dedup by source URL
        $check = $pdo->prepare("SELECT id FROM gifs WHERE source_url = ?");
        $check->execute([$src['url']]);
        if ($check->fetch()) continue;

        // Also dedup by title+keywords (avoid near-duplicates)
        $check2 = $pdo->prepare("SELECT id FROM gifs WHERE title = ? AND keywords = ?");
        $check2->execute([$src['title'], $src['keywords']]);
        if ($check2->fetch()) continue;

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

        // Validate GIF header
        if (substr($data, 0, 6) !== 'GIF87a' && substr($data, 0, 6) !== 'GIF89a') continue;

        $imgData = base64_encode($data);
        $ch = curl_init('https://api.imgbb.com/1/upload');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['key' => $imgbbKey, 'image' => $imgData, 'name' => 'og_' . time()],
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
