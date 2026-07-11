<?php

use function Env\env;

function handleUpload(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $error = null;
        require __DIR__ . '/../templates/upload.php';
        return;
    }

    $title = trim($_POST['title'] ?? '');
    $keywords = trim($_POST['keywords'] ?? '');
    $file = $_FILES['image'] ?? null;

    $errors = [];

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed.';
    } elseif ($file['size'] > 32 * 1024 * 1024) {
        $errors[] = 'File exceeds 32 MB limit.';
    } elseif (mime_content_type($file['tmp_name']) !== 'image/gif') {
        $errors[] = 'Only GIF files are allowed.';
    }

    if ($errors) {
        $error = implode(' ', $errors);
        require __DIR__ . '/../templates/upload.php';
        return;
    }

    $apiKey = env('IMGBB_API_KEY');
    if (!$apiKey) {
        $error = 'IMGBB_API_KEY is not configured.';
        require __DIR__ . '/../templates/upload.php';
        return;
    }

    $imageData = base64_encode(file_get_contents($file['tmp_name']));

    $ch = curl_init('https://api.imgbb.com/1/upload');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'key' => $apiKey,
            'image' => $imageData,
            'name' => pathinfo($file['name'], PATHINFO_FILENAME),
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $error = 'Failed to upload to image host. Please try again.';
        require __DIR__ . '/../templates/upload.php';
        return;
    }

    $data = json_decode($response, true);

    if (!($data['data']['url'] ?? null)) {
        $error = 'Invalid response from image host.';
        require __DIR__ . '/../templates/upload.php';
        return;
    }

    $proxyPath = bin2hex(random_bytes(12));

    $pdo = getDb();
    $stmt = $pdo->prepare("
        INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, proxy_path, file_size, mime_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $title ?: null,
        $keywords ?: null,
        $file['name'],
        $data['data']['url'],
        $data['data']['delete_url'] ?? null,
        $proxyPath,
        $data['data']['size'] ?? $file['size'],
        'image/gif',
    ]);

    $gifId = $data['data']['id'] ?? $proxyPath;

    header('Location: /gif/' . $proxyPath);
    exit;
}
