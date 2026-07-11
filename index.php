<?php

require __DIR__ . '/config.php';

try {
    ensureTable();
} catch (Exception $e) {
    $error = $e->getMessage();
    require __DIR__ . '/templates/setup.php';
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// Serve static files from /public/ directly
if (preg_match('#^/public/(.+)$#', $uri, $m)) {
    $file = __DIR__ . '/public/' . $m[1];
    if (file_exists($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = ['css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon'];
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
}

// ─── API ────────────────────────────────────────────────
if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/handlers/api.php';
    handleApi($uri, $_GET);
    exit;
}

// ─── ROUTES ──────────────────────────────────────────────

// Home
if ($uri === '/' || $uri === '') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 24;
    $offset = ($page - 1) * $perPage;

    $pdo = getDb();
    $total = $pdo->query("SELECT COUNT(*) FROM gifs")->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    $stmt = $pdo->prepare("SELECT * FROM gifs ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $gifs = $stmt->fetchAll();

    require __DIR__ . '/templates/home.php';
    exit;
}

// Upload form
if ($uri === '/upload' && $method === 'GET') {
    $error = null;
    require __DIR__ . '/templates/upload.php';
    exit;
}

// Upload handler
if ($uri === '/upload' && $method === 'POST') {
    require __DIR__ . '/handlers/upload.php';
    handleUpload();
    exit;
}

// Search
if ($uri === '/search') {
    $query = trim($_GET['q'] ?? '');
    if (!$query) {
        header('Location: /');
        exit;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 24;
    $offset = ($page - 1) * $perPage;

    $pdo = getDb();
    $like = '%' . $query . '%';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gifs WHERE keywords LIKE ? OR title LIKE ?");
    $stmt->execute([$like, $like]);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    $stmt = $pdo->prepare("SELECT * FROM gifs WHERE keywords LIKE ? OR title LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $like, PDO::PARAM_STR);
    $stmt->bindValue(2, $like, PDO::PARAM_STR);
    $stmt->bindValue(3, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $gifs = $stmt->fetchAll();

    require __DIR__ . '/templates/search.php';
    exit;
}

// GIF page
if (preg_match('#^/gif/([a-f0-9]+)$#', $uri, $m)) {
    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT * FROM gifs WHERE proxy_path = ?");
    $stmt->execute([$m[1]]);
    $gif = $stmt->fetch();

    if (!$gif) {
        http_response_code(404);
        echo '<h1>404 — GIF not found</h1>';
        exit;
    }

    $pdo->prepare("UPDATE gifs SET views = views + 1 WHERE id = ?")->execute([$gif['id']]);
    $gif['views']++;

    $stmt = $pdo->prepare("SELECT * FROM gifs WHERE id != ? ORDER BY created_at DESC LIMIT 12");
    $stmt->execute([$gif['id']]);
    $related = $stmt->fetchAll();

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

    require __DIR__ . '/templates/gif.php';
    exit;
}

// Proxy (serve GIF from imgBB, hiding real URL)
if (preg_match('#^/g/([a-f0-9]+)$#', $uri, $m)) {
    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT imgbb_url, mime_type FROM gifs WHERE proxy_path = ?");
    $stmt->execute([$m[1]]);
    $gif = $stmt->fetch();

    if (!$gif) {
        http_response_code(404);
        echo '<h1>404 — GIF not found</h1>';
        exit;
    }

    $ch = curl_init($gif['imgbb_url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(502);
        echo '<h1>502 — Failed to fetch GIF</h1>';
        exit;
    }

    header('Content-Type: ' . ($gif['mime_type'] ?: 'image/gif'));
    header('Content-Length: ' . strlen($body));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Proxy-By: OpenGifs');
    echo $body;
    exit;
}

// API docs
if ($uri === '/api' || $uri === '/api/') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
    require __DIR__ . '/templates/api_docs.php';
    exit;
}

// Rules
if ($uri === '/rules') {
    require __DIR__ . '/templates/rules.php';
    exit;
}

// 404
http_response_code(404);
echo '<h1>404 — Page not found</h1>';
