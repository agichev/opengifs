<?php

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $name = env('DB_DATABASE', 'opengifs');
        $user = env('DB_USERNAME', 'root');
        $pass = env('DB_PASSWORD', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function ensureTable(): void
{
    try {
        $pdo = getDb();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS gifs (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                title           VARCHAR(255) DEFAULT NULL,
                keywords        VARCHAR(500) DEFAULT NULL,
                original_name   VARCHAR(255) DEFAULT NULL,
                imgbb_url       VARCHAR(500) NOT NULL,
                imgbb_delete_url VARCHAR(500) DEFAULT NULL,
                proxy_path      VARCHAR(100) NOT NULL UNIQUE,
                file_size       INT DEFAULT 0,
                mime_type       VARCHAR(50) DEFAULT 'image/gif',
                views           INT DEFAULT 0,
                created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_keywords (keywords),
                INDEX idx_proxy_path (proxy_path),
                INDEX idx_created_at (created_at),
                INDEX idx_views (views)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        http_response_code(500);
        require __DIR__ . '/templates/setup.php';
        exit;
    }
}
