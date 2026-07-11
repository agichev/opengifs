<?php

function autoPopulate(int $count = 6, bool $force = false): void
{
    $pdo = getDb();
    $imgbbKey = env('IMGBB_API_KEY');
    $giphyKey = env('GIPHY_API_KEY');
    $pixabayKey = env('PIXABAY_API_KEY');

    if (!$imgbbKey) return;

    if (!$force) {
        $total = $pdo->query("SELECT COUNT(*) FROM gifs")->fetchColumn();
        if ($total >= 10) return;
    }

    $sources = [];

    // ── 1. PIXABAY (5000 req/h) — search with "gif" suffix, image_type=all ──
    if ($pixabayKey) {
        $pixQueries = ['funny gif', 'cat gif', 'dance gif', 'animals gif', 'reaction gif', 'happy gif',
                       'baby gif', 'dog gif', 'love gif', 'party gif', 'celebration gif', 'sport gif',
                       'music gif', 'fail gif', 'cute gif'];
        shuffle($pixQueries);
        $used = 0;

        foreach ($pixQueries as $q) {
            if ($used >= 4) break;

            $resp = @file_get_contents("https://pixabay.com/api/?key={$pixabayKey}&q=" . urlencode($q) . "&image_type=all&per_page=30&safesearch=true&order=popular");
            if (!$resp) continue;

            $data = json_decode($resp, true);
            if (empty($data['hits'])) continue;
            $used++;

            foreach ($data['hits'] as $img) {
                $url = $img['webformatURL'] ?? '';
                $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                if ($ext !== 'gif') continue;

                $tags = $img['tags'] ?? $q;
                $sources[] = [
                    'url' => $url,
                    'type' => 'pixabay',
                    'title' => ($img['user'] ?? 'Pixabay') . ' — ' . str_replace(' gif', '', $q),
                    'keywords' => 'pixabay, ' . str_replace(', ', ', ', $tags),
                ];
            }
        }
    }

    // ── 2. GIPHY (100 req/h) — trending + search ──
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

        $searchTerms = ['funny', 'cat', 'dance', 'animals', 'reaction', 'happy', 'love', 'fail', 'cute'];
        shuffle($searchTerms);
        $searched = 0;
        foreach (array_unique($searchTerms) as $q) {
            if ($searched >= 2) break;
            $resp = @file_get_contents("https://api.giphy.com/v1/gifs/search?api_key={$giphyKey}&q=" . urlencode($q) . "&limit={$count}&rating=g&offset=" . rand(0, 20));
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
            CURLOPT_TIMEOUT => 15,
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
            CURLOPT_POSTFIELDS => ['key' => $imgbbKey, 'image' => $imageData, 'name' => 'opengifs_' . time()],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) continue;

        $up = json_decode($resp, true);
        if (!($up['data']['url'] ?? null)) continue;

        $stmt = $pdo->prepare("INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, source_url, proxy_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            mb_substr($src['title'], 0, 255),
            mb_substr($src['keywords'], 0, 500),
            'auto_' . time() . '.gif',
            $up['data']['url'],
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
