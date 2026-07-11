<?php

function handleApi(string $path, array $query): void
{
    header('Content-Type: application/json');

    $pdo = getDb();

    if (preg_match('#^/api/v1/gifs/search$#', $path)) {
        $q = trim($query['q'] ?? '');
        if (!$q) {
            http_response_code(422);
            echo json_encode(['message' => 'The q field is required.', 'errors' => ['q' => ['The q field is required.']]]);
            return;
        }
        $limit = min((int)($query['limit'] ?? 20), 50);
        $stmt = $pdo->prepare("SELECT * FROM gifs WHERE keywords LIKE ? OR title LIKE ? ORDER BY created_at DESC LIMIT ?");
        $like = '%' . $q . '%';
        $stmt->bindParam(1, $like, PDO::PARAM_STR);
        $stmt->bindParam(2, $like, PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $gifs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'query' => $q, 'count' => count($gifs), 'data' => array_map('formatGif', $gifs)]);
        return;
    }

    if (preg_match('#^/api/v1/gifs/trending$#', $path)) {
        $limit = min((int)($query['limit'] ?? 20), 50);
        $stmt = $pdo->prepare("SELECT * FROM gifs ORDER BY views DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $gifs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'count' => count($gifs), 'data' => array_map('formatGif', $gifs)]);
        return;
    }

    if (preg_match('#^/api/v1/gifs/latest$#', $path)) {
        $limit = min((int)($query['limit'] ?? 20), 50);
        $stmt = $pdo->prepare("SELECT * FROM gifs ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $gifs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'count' => count($gifs), 'data' => array_map('formatGif', $gifs)]);
        return;
    }

    if (preg_match('#^/api/v1/gifs/random$#', $path)) {
        $stmt = $pdo->query("SELECT * FROM gifs ORDER BY RAND() LIMIT 1");
        $gif = $stmt->fetch();
        echo json_encode(['success' => true, 'data' => $gif ? formatGif($gif) : null]);
        return;
    }

    if (preg_match('#^/api/v1/gifs/(\d+)$#', $path, $m)) {
        $stmt = $pdo->prepare("SELECT * FROM gifs WHERE id = ?");
        $stmt->execute([(int)$m[1]]);
        $gif = $stmt->fetch();
        if (!$gif) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Not found']);
            return;
        }
        echo json_encode(['success' => true, 'data' => formatGif($gif)]);
        return;
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Not found']);
}

function formatGif(array $gif): array
{
    $base = getBaseUrl();
    return [
        'id' => (int)$gif['id'],
        'title' => $gif['title'],
        'keywords' => $gif['keywords'] ? explode(',', $gif['keywords']) : [],
        'url' => $base . '/gif/' . $gif['proxy_path'],
        'gif_url' => $base . '/g/' . $gif['proxy_path'] . '.gif',
        'file_size' => (int)$gif['file_size'],
        'views' => (int)$gif['views'],
        'created_at' => date('c', strtotime($gif['created_at'])),
    ];
}

function getBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}
