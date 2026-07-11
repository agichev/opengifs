<?php

namespace App\Http\Controllers;

use App\Models\Gif;
use Illuminate\Support\Facades\Http;

class ProxyController extends Controller
{
    public function proxy(string $proxyPath)
    {
        $gif = Gif::where('proxy_path', $proxyPath)->firstOrFail();

        $response = Http::timeout(15)->get($gif->imgbb_url);

        if (!$response->successful()) {
            abort(404);
        }

        return response($response->body(), 200, [
            'Content-Type' => $gif->mime_type ?: 'image/gif',
            'Content-Length' => strlen($response->body()),
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Proxy-By' => 'OpenGifs',
        ]);
    }
}
