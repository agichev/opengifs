<?php

namespace App\Http\Controllers;

use App\Models\Gif;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 20);

        $gifs = Gif::search($query)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($gif) => $this->formatGif($gif));

        return response()->json([
            'success' => true,
            'query' => $query,
            'count' => $gifs->count(),
            'data' => $gifs,
        ]);
    }

    public function trending(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);

        $gifs = Gif::trending()
            ->limit($limit)
            ->get()
            ->map(fn($gif) => $this->formatGif($gif));

        return response()->json([
            'success' => true,
            'count' => $gifs->count(),
            'data' => $gifs,
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);

        $gifs = Gif::latest()
            ->limit($limit)
            ->get()
            ->map(fn($gif) => $this->formatGif($gif));

        return response()->json([
            'success' => true,
            'count' => $gifs->count(),
            'data' => $gifs,
        ]);
    }

    public function random(): JsonResponse
    {
        $count = Gif::count();

        if ($count === 0) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $gif = Gif::inRandomOrder()->first();

        return response()->json([
            'success' => true,
            'data' => $this->formatGif($gif),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $gif = Gif::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatGif($gif),
        ]);
    }

    protected function formatGif(Gif $gif): array
    {
        return [
            'id' => $gif->id,
            'title' => $gif->title,
            'keywords' => $gif->keywords ? explode(',', $gif->keywords) : [],
            'url' => route('gifs.show', $gif->proxy_path),
            'gif_url' => route('gifs.proxy', $gif->proxy_path),
            'file_size' => (int) $gif->file_size,
            'views' => (int) $gif->views,
            'created_at' => $gif->created_at->toISOString(),
        ];
    }
}
