<?php

function handleUpload(): void
{
    try {
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
        } elseif (!function_exists('mime_content_type') || mime_content_type($file['tmp_name']) !== 'image/gif') {
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
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $error = 'Upload failed: ' . $curlError;
            require __DIR__ . '/../templates/upload.php';
            return;
        }

        if ($httpCode !== 200) {
            $error = 'Image host returned HTTP ' . $httpCode;
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
        // Use display_url (direct image URL), fallback to url
        $imgbbUrl = $data['data']['display_url'] ?? $data['data']['url'];
        $stmt = $pdo->prepare("
            INSERT INTO gifs (title, keywords, original_name, imgbb_url, imgbb_delete_url, proxy_path, file_size, mime_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title ?: null,
            $keywords ?: null,
            $file['name'],
            $imgbbUrl,
            $data['data']['delete_url'] ?? null,
            $proxyPath,
            $data['data']['size'] ?? $file['size'],
            'image/gif',
        ]);

        header('Location: /gif/' . $proxyPath);
        exit;

    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Upload error: ' . htmlspecialchars($e->getMessage());
        exit;
    }
}
