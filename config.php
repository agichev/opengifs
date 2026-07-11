<?php

function env(string $key, mixed $default = null): mixed
{
    // Most PHP hosting puts env vars in $_SERVER
    if (isset($_SERVER[$key])) {
        return $_SERVER[$key];
    }
    // Some put them in $_ENV
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    // getenv as last resort
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    return $default;
}

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Support DATABASE_URL (common on Railway, Heroku, etc.)
        $url = env('DATABASE_URL');
        if ($url) {
            $parsed = parse_url($url);
            $host = $parsed['host'] ?? '127.0.0.1';
            $port = $parsed['port'] ?? '3306';
            $user = $parsed['user'] ?? 'root';
            $pass = $parsed['pass'] ?? '';
            $name = ltrim($parsed['path'] ?? 'opengifs', '/');
        } else {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $user = env('DB_USERNAME', 'root');
            $pass = env('DB_PASSWORD', '');
            $name = env('DB_DATABASE', 'opengifs');
        }

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
        $error = $e->getMessage();
        require __DIR__ . '/templates/setup.php';
        exit;
    }
}
