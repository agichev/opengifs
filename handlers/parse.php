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

    $keywords = [];

    // ── Pixabay: get trending topics (5000 req/h, use freely) ──
    if ($pixabayKey) {
        // Get recent popular images across categories to extract keywords
        $resp = @file_get_contents("https://pixabay.com/api/?key={$pixabayKey}&per_page=50&safesearch=true&order=popular&editors_choice=true");
        if ($resp) {
            $data = json_decode($resp, true);
            foreach ($data['hits'] ?? [] as $img) {
                $tags = explode(', ', $img['tags'] ?? '');
                foreach ($tags as $t) {
                    $t = trim($t);
                    if (strlen($t) > 2 && strlen($t) < 30) {
                        $keywords[$t] = ($keywords[$t] ?? 0) + 1;
                    }
                }
            }
        }

        // Also search specific categories to get fresh topics
        $cats = ['animals', 'sports', 'music', 'food', 'nature', 'people', 'feelings', 'travel'];
        foreach ($cats as $cat) {
            $resp = @file_get_contents("https://pixabay.com/api/?key={$pixabayKey}&category={$cat}&per_page=20&safesearch=true&order=popular");
            if (!$resp) continue;
            $data = json_decode($resp, true);
            foreach ($data['hits'] ?? [] as $img) {
                $tags = explode(', ', $img['tags'] ?? '');
                foreach ($tags as $t) {
                    $t = trim($t);
                    if (strlen($t) > 2 && strlen($t) < 30) {
                        $keywords[$t] = ($keywords[$t] ?? 0) + 1;
                    }
                }
            }
        }
    }

    // ── GIPHY: search trending + our keywords (100 req/h, be smart) ──
    $giphySearches = [];

    if ($giphyKey) {
        // Always get trending first
        $giphySearches[] = null; // null = trending

        // Add top keywords from Pixabay (max 10, 50 per search = 500 GIFs)
        arsort($keywords);
        $topKeywords = array_slice(array_keys($keywords), 0, 10);
        foreach ($topKeywords as $kw) {
            if (count($giphySearches) >= 4) break; // limit to 4 GIPHY calls
            $giphySearches[] = $kw;
        }
    }

    $sources = [];

    foreach ($giphySearches as $q) {
        $url = $q === null
            ? "https://api.giphy.com/v1/gifs/trending?api_key={$giphyKey}&limit={$count}&rating=g"
            : "https://api.giphy.com/v1/gifs/search?api_key={$giphyKey}&q=" . urlencode($q) . "&limit={$count}&rating=g";

        $resp = @file_get_contents($url);
        if (!$resp) continue;

        $data = json_decode($resp, true);
        foreach ($data['data'] ?? [] as $g) {
            $gifUrl = $g['images']['original']['url'] ?? null;
            if (!$gifUrl) continue;

            $slug = $g['slug'] ?? '';
            $title = $g['title'] ?: 'GIF';
            $kw = 'giphy' . ($slug ? ', ' . $slug : '');
            if ($q) $kw .= ', ' . $q;

            $sources[] = ['url' => $gifUrl, 'title' => $title, 'keywords' => $kw];
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
            mb_substr($src['title'], 0, 255),
            mb_substr($src['keywords'], 0, 500),
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
