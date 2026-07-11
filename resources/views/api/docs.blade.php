@extends('layouts.app')

@section('title', 'API Documentation')

@section('content')
    <h1 class="page-title">API Documentation</h1>

    <div class="api-docs">
        <p>
            OpenGifs provides a free, open REST API for searching and retrieving GIFs.
            <strong>No API key is required.</strong>
        </p>

        <h2>Base URL</h2>
        <div class="endpoint">{{ url('/api/v1') }}</div>

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

        <table style="width:100%;border-collapse:collapse;margin:12px 0;font-size:13px;">
            <tr style="background:#e8f0fe;">
                <th style="text-align:left;padding:8px 12px;border:1px solid #d0d8e8;">Field</th>
                <th style="text-align:left;padding:8px 12px;border:1px solid #d0d8e8;">Type</th>
                <th style="text-align:left;padding:8px 12px;border:1px solid #d0d8e8;">Description</th>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;"><code>id</code></td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">integer</td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">Unique identifier</td>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;"><code>title</code></td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">string|null</td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">GIF title</td>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;"><code>keywords</code></td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">array</td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">Search keywords</td>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;"><code>url</code></td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">string</td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">Page URL on OpenGifs</td>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;"><code>gif_url</code></td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">string</td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;"><strong>Direct GIF URL</strong> (proxied — use this for embedding)</td>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;"><code>file_size</code></td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">integer</td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">File size in bytes</td>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;"><code>views</code></td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">integer</td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">View count</td>
            </tr>
            <tr>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;"><code>created_at</code></td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">string</td>
                <td style="padding:6px 12px;border:1px solid #d0d8e8;">ISO 8601 timestamp</td>
            </tr>
        </table>

        <h2 id="endpoints">Endpoints</h2>

        <h3>Search GIFs</h3>
        <div class="endpoint">GET /api/v1/gifs/search?q={query}&limit={count}</div>
        <p>Search GIFs by keywords.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>q</code> (required) — search query, max 100 characters</li>
            <li><code>limit</code> (optional, default: 20, max: 50) — number of results</li>
        </ul>
        <p><strong>Example request:</strong></p>
        <pre>GET {{ url('/api/v1/gifs/search') }}?q=cat&limit=3</pre>
        <p><strong>Example response:</strong></p>
        <pre>{
    "success": true,
    "query": "cat",
    "count": 3,
    "data": [
        {
            "id": 1,
            "title": "Funny cat dancing",
            "keywords": ["cat", "funny", "dance"],
            "url": "{{ url('/gif/abc123') }}",
            "gif_url": "{{ url('/g/abc123') }}",
            "file_size": 1234567,
            "views": 42,
            "created_at": "2026-01-15T12:00:00+00:00"
        }
    ]
}</pre>

        <h3>Trending GIFs</h3>
        <div class="endpoint">GET /api/v1/gifs/trending?limit={count}</div>
        <p>Get the most viewed GIFs.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>limit</code> (optional, default: 20, max: 50)</li>
        </ul>

        <h3>Latest GIFs</h3>
        <div class="endpoint">GET /api/v1/gifs/latest?limit={count}</div>
        <p>Get the most recently uploaded GIFs.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>limit</code> (optional, default: 20, max: 50)</li>
        </ul>

        <h3>Random GIF</h3>
        <div class="endpoint">GET /api/v1/gifs/random</div>
        <p>Get a random GIF. Returns <code>null</code> if no GIFs exist.</p>

        <h3>Get GIF by ID</h3>
        <div class="endpoint">GET /api/v1/gifs/{id}</div>
        <p>Get details for a specific GIF by its ID.</p>

        <h2 id="integration">Integration Examples</h2>

        <h3>JavaScript (Fetch)</h3>
        <pre>fetch('{{ url('/api/v1/gifs/search') }}?q=cat&limit=5')
    .then(response => response.json())
    .then(data => {
        data.data.forEach(gif => {
            const img = document.createElement('img');
            img.src = gif.gif_url;
            img.alt = gif.title || 'GIF';
            document.body.appendChild(img);
        });
    });</pre>

        <h3>JavaScript (jQuery)</h3>
        <pre>$.getJSON('{{ url('/api/v1/gifs/search') }}', { q: 'cat', limit: 5 }, function(data) {
    $.each(data.data, function(i, gif) {
        $('#gif-container').append(
            $('&lt;img&gt;').attr('src', gif.gif_url).attr('alt', gif.title)
        );
    });
});</pre>

        <h3>PHP</h3>
        <pre>&lt;?php
$url = '{{ url('/api/v1/gifs/search') }}?q=cat&amp;limit=5';
$response = file_get_contents($url);
$data = json_decode($response, true);

foreach ($data['data'] as $gif) {
    echo '&lt;img src="' . htmlspecialchars($gif['gif_url']) . '"'
       . ' alt="' . htmlspecialchars($gif['title'] ?? 'GIF') . '"&gt;';
}</pre>

        <h3>PHP (cURL)</h3>
        <pre>&lt;?php
$ch = curl_init('{{ url('/api/v1/gifs/search') }}?q=cat&amp;limit=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

foreach ($data['data'] as $gif) {
    echo '&lt;img src="' . htmlspecialchars($gif['gif_url']) . '"
           alt="' . htmlspecialchars($gif['title'] ?? 'GIF') . '"&gt;';
}</pre>

        <h3>Python</h3>
        <pre>import requests
import json

response = requests.get('{{ url('/api/v1/gifs/search') }}', params={
    'q': 'cat',
    'limit': 5
})
data = response.json()

for gif in data['data']:
    print(f"Title: {gif['title']}")
    print(f"URL: {gif['gif_url']}")
    print('---')</pre>

        <h3>Python (aiohttp async)</h3>
        <pre>import aiohttp
import asyncio

async def fetch_gifs():
    async with aiohttp.ClientSession() as session:
        async with session.get(
            '{{ url('/api/v1/gifs/search') }}',
            params={'q': 'cat', 'limit': 5}
        ) as response:
            data = await response.json()
            return data['data']

gifs = asyncio.run(fetch_gifs())
for gif in gifs:
    print(f"URL: {gif['gif_url']}")</pre>

        <h3>Ruby</h3>
        <pre>require 'net/http'
require 'json'

url = URI('{{ url('/api/v1/gifs/search') }}')
url.query = URI.encode_www_form({ q: 'cat', limit: 5 })

response = Net::HTTP.get(url)
data = JSON.parse(response)

data['data'].each do |gif|
    puts "Title: #{gif['title']}"
    puts "URL: #{gif['gif_url']}"
end</pre>

        <h3>cURL</h3>
        <pre>curl "{{ url('/api/v1/gifs/search') }}?q=cat&limit=5"</pre>

        <h3>Go</h3>
        <pre>package main

import (
    "encoding/json"
    "fmt"
    "io"
    "net/http"
)

type Gif struct {
    ID     int      `json:"id"`
    Title  string   `json:"title"`
    GIFUrl string   `json:"gif_url"`
}

type Response struct {
    Data []Gif `json:"data"`
}

func main() {
    url := "{{ url('/api/v1/gifs/search') }}?q=cat&limit=5"
    resp, _ := http.Get(url)
    body, _ := io.ReadAll(resp.Body)
    defer resp.Body.Close()

    var result Response
    json.Unmarshal(body, &result)

    for _, gif := range result.Data {
        fmt.Println(gif.GIFUrl)
    }
}</pre>

        <h2 id="embedding">Embedding GIFs</h2>
        <p>Use the <code>gif_url</code> field from the API response to embed GIFs directly:</p>

        <h3>HTML</h3>
        <pre>&lt;img src="https://opengifs.com/g/abc123" alt="Funny cat"&gt;</pre>

        <h3>Markdown</h3>
        <pre>![Funny cat](https://opengifs.com/g/abc123)</pre>

        <h3>BBCode (forums)</h3>
        <pre>[img]https://opengifs.com/g/abc123[/img]</pre>

        <h3>Discord</h3>
        <pre>https://opengifs.com/g/abc123</pre>
        <p>Discord will automatically embed the GIF.</p>

        <h2 id="errors">Error Handling</h2>
        <p>The API returns standard HTTP status codes:</p>
        <ul>
            <li><code>200</code> — Success</li>
            <li><code>404</code> — Resource not found</li>
            <li><code>422</code> — Validation error (e.g., missing required parameter)</li>
            <li><code>429</code> — Rate limit exceeded</li>
        </ul>
        <p>Validation errors include a message in the response body:</p>
        <pre>{
    "message": "The q field is required.",
    "errors": {
        "q": ["The q field is required."]
    }
}</pre>

        <h2 id="faq">FAQ</h2>

        <h3>Do I need an API key?</h3>
        <p>No. The OpenGifs API is completely open and free to use.</p>

        <h3>What is the rate limit?</h3>
        <p>120 requests per minute per IP address.</p>

        <h3>Can I use the GIF URLs in my app?</h3>
        <p>Yes, feel free to embed or hotlink GIFs using the <code>gif_url</code> from the API response.</p>

        <h3>How do I upload a GIF via API?</h3>
        <p>There is no upload API at this time. Please use the web interface at <a href="{{ route('gifs.create') }}">{{ route('gifs.create') }}</a>.</p>

        <p style="margin-top:40px;color:#888;font-size:13px;border-top:1px solid #e0e8f0;padding-top:20px;">
            OpenGifs API v1 — No key required. Free for everyone.
        </p>
    </div>
@endsection
