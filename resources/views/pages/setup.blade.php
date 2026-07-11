<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenGifs — Setup Required</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #e8f0fe;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .setup-box {
            max-width: 600px;
            margin: 40px 20px;
            background: #fff;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 { color: #dc3545; margin-bottom: 16px; font-size: 28px; }
        p { color: #666; font-size: 14px; line-height: 1.7; margin-bottom: 12px; }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
        .logo {
            font-size: 36px;
            font-weight: bold;
            color: #4a90d9;
            margin-bottom: 20px;
        }
        .logo span { color: #ffd700; }
    </style>
</head>
<body>
    <div class="setup-box">
        <div class="logo">Open<span>Gifs</span></div>
        <h1>Database Connection Error</h1>
        <p>
            OpenGifs could not connect to the database or the required table does not exist.
        </p>
        <p>
            Please make sure the following environment variables are set in your hosting environment:
        </p>
        <p>
            <code>APP_KEY</code> (run: <code>php artisan key:generate</code>),<br>
            <code>DB_CONNECTION</code>, <code>DB_HOST</code>, <code>DB_PORT</code>,<br>
            <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>,<br>
            <code>IMGBB_API_KEY</code> (get it at <a href="https://api.imgbb.com" target="_blank" style="color:#4a90d9;">api.imgbb.com</a>)
        </p>
        @if(isset($error))
            <p style="color:#dc3545;font-size:13px;margin-top:12px;">
                Error: {{ $error }}
            </p>
        @endif
        <p style="margin-top:16px;font-size:13px;color:#999;">
            Once configured, refresh the page.
        </p>
    </div>
</body>
</html>
