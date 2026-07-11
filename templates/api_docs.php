<?php
$pageTitle = 'API Documentation';
require __DIR__ . '/header.php';
?>

<h1 class="page-title">API Documentation</h1>

<div class="api-docs">
    <p>
        OpenGifs provides a free, open REST API for searching and retrieving GIFs.
        <strong>No API key is required.</strong>
    </p>

    <h2>Base URL</h2>
    <div class="endpoint"><?= $baseUrl ?>/api/v1</div>

    <h2>Authentication</h2>
    <p>None required. All endpoints are publicly accessible.</p>

    <h2>Rate Limiting</h2>
    <p>120 requests per minute per IP address.</p>

    <h2>Response Format</h2>
    <p>All responses are in JSON format:</p>
    <pre>{
    "success": true,
    "count": 1,
    "data": [ ... ]
}</pre>

    <h2>Endpoints</h2>

    <h3>Search GIFs</h3>
    <div class="endpoint">GET /api/v1/gifs/search?q={query}&amp;limit={count}</div>
    <p>Search GIFs by keywords.</p>
    <ul>
        <li><code>q</code> (required) — search query</li>
        <li><code>limit</code> (optional, default: 20, max: 50)</li>
    </ul>

    <h3>Trending GIFs</h3>
    <div class="endpoint">GET /api/v1/gifs/trending?limit={count}</div>
    <p>Most viewed GIFs.</p>

    <h3>Latest GIFs</h3>
    <div class="endpoint">GET /api/v1/gifs/latest?limit={count}</div>
    <p>Most recently uploaded GIFs.</p>

    <h3>Random GIF</h3>
    <div class="endpoint">GET /api/v1/gifs/random</div>
    <p>Get a random GIF.</p>

    <h3>Get GIF by ID</h3>
    <div class="endpoint">GET /api/v1/gifs/{id}</div>

    <h2>Integration Examples</h2>

    <h3>JavaScript (Fetch)</h3>
    <pre>fetch('<?= $baseUrl ?>/api/v1/gifs/search?q=cat&limit=5')
    .then(r => r.json())
    .then(data => {
        data.data.forEach(gif => {
            const img = document.createElement('img');
            img.src = gif.gif_url;
            document.body.appendChild(img);
        });
    });</pre>

    <h3>JavaScript (jQuery)</h3>
    <pre>$.getJSON('<?= $baseUrl ?>/api/v1/gifs/search', { q: 'cat', limit: 5 }, function(data) {
    $.each(data.data, function(i, gif) {
        $('#container').append($('&lt;img&gt;').attr('src', gif.gif_url));
    });
});</pre>

    <h3>PHP</h3>
    <pre>&lt;?php
$url = '<?= $baseUrl ?>/api/v1/gifs/search?q=cat&amp;limit=5';
$data = json_decode(file_get_contents($url), true);
foreach ($data['data'] as $gif) {
    echo '&lt;img src="' . htmlspecialchars($gif['gif_url']) . '"&gt;';
}</pre>

    <h3>Python</h3>
    <pre>import requests
r = requests.get('<?= $baseUrl ?>/api/v1/gifs/search', params={'q': 'cat', 'limit': 5})
for gif in r.json()['data']:
    print(gif['gif_url'])</pre>

    <h3>cURL</h3>
    <pre>curl "<?= $baseUrl ?>/api/v1/gifs/search?q=cat&limit=5"</pre>

    <h2>Response Fields</h2>
    <ul>
        <li><code>id</code> — unique identifier</li>
        <li><code>title</code> — GIF title</li>
        <li><code>keywords</code> — search keywords (array)</li>
        <li><code>url</code> — page URL on OpenGifs</li>
        <li><code>gif_url</code> — direct GIF URL (proxied, use for embedding)</li>
        <li><code>file_size</code> — size in bytes</li>
        <li><code>views</code> — view count</li>
        <li><code>created_at</code> — ISO 8601 timestamp</li>
    </ul>

    <p style="margin-top:40px;color:#888;font-size:13px;border-top:1px solid #e0e8f0;padding-top:20px;">
        OpenGifs API v1 — No key required. Free for everyone.
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>
