<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenGifs — Setup Required</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <style>
        .setup-box {
            max-width: 600px;
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
    </style>
</head>
<body>
    <div class="setup-box">
        <h1>Database Connection Error</h1>
        <p>OpenGifs could not connect to the database or the table does not exist.</p>
        <p>Set these environment variables in your hosting dashboard:</p>
        <p>
            <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_DATABASE</code>,<br>
            <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>,<br>
            <code>IMGBB_API_KEY</code>
        </p>
        <?php if (isset($error) && $error): ?>
            <p style="color:#dc3545;font-size:13px;margin-top:12px;">Error: <?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <p style="margin-top:16px;font-size:13px;color:#999;">Once configured, refresh the page.</p>
    </div>
</body>
</html>
<?php exit; ?>
