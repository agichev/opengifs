<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenGifs — Setup Required</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <style>
        .setup-box {
            max-width: 680px;
            margin: 60px auto;
            background: #fff;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .setup-box h1 { color: #dc3545; margin-bottom: 16px; font-size: 28px; }
        .setup-box p { color: #666; font-size: 14px; line-height: 1.7; margin-bottom: 12px; }
        .setup-box code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        .setup-box a { color: #4a90d9; }
        .setup-box .debug { text-align: left; background: #f8f8f8; border: 1px solid #ddd; border-radius: 4px; padding: 12px; margin-top: 16px; font-size: 12px; color: #555; word-break: break-all; }
        .setup-box .debug strong { color: #333; }
    </style>
</head>
<body>
    <div class="setup-box">
        <h1>Database Connection Error</h1>
        <p>OpenGifs could not connect to the database.</p>
        <p>Set these environment variables in your hosting dashboard:</p>
        <p>
            <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>,<br>
            <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>,<br>
            <code>IMGBB_API_KEY</code>
        </p>
        <p style="font-size:13px;color:#999;">
            Or set a single <code>DATABASE_URL</code> like:<br>
            <code>mysql://user:pass@host:3306/dbname</code>
        </p>
        <?php if (isset($error) && $error): ?>
            <div class="debug">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?><br><br>
                <strong>DB_HOST:</strong> <?= htmlspecialchars(env('DB_HOST', '(not set)')) ?><br>
                <strong>DB_PORT:</strong> <?= htmlspecialchars(env('DB_PORT', '(not set)')) ?><br>
                <strong>DB_NAME:</strong> <?= htmlspecialchars(env('DB_NAME', '(not set)')) ?><br>
                <strong>DB_DATABASE:</strong> <?= htmlspecialchars(env('DB_DATABASE', '(not set)')) ?><br>
                <strong>DB_USERNAME:</strong> <?= htmlspecialchars(env('DB_USERNAME', '(not set)')) ?><br>
                <strong>DB_PASSWORD:</strong> <?= env('DB_PASSWORD') ? '********' : '(not set)' ?><br>
                <strong>DATABASE_URL:</strong> <?= htmlspecialchars(env('DATABASE_URL', '(not set)')) ?>
            </div>
        <?php endif; ?>
        <p style="margin-top:16px;font-size:13px;color:#999;">Once configured, refresh the page.</p>
    </div>
</body>
</html>
