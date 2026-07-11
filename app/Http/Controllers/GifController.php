<?php

namespace App\Http\Controllers;

use App\Models\Gif;
use App\Services\ImgBBService;
use Illuminate\Http\Request;

class GifController extends Controller
{
    public function index()
    {
        $gifs = Gif::latest()->paginate(24);
        return view('gifs.index', compact('gifs'));
    }

    public function create()
    {
        return view('gifs.upload');
    }

    public function store(Request $request, ImgBBService $imgbb)
    {
        $request->validate([
            'image' => 'required|file|mimes:gif|max:32768',
            'title' => 'nullable|string|max:255',
            'keywords' => 'nullable|string|max:500',
        ]);

        $file = $request->file('image');

        $uploaded = $imgbb->upload($file);

        $gif = Gif::create([
            'title' => $request->input('title'),
            'keywords' => $request->input('keywords'),
            'original_name' => $file->getClientOriginalName(),
            'imgbb_url' => $uploaded['url'],
            'imgbb_delete_url' => $uploaded['delete_url'],
            'proxy_path' => ImgBBService::generateProxyPath(),
            'file_size' => $uploaded['size'],
            'mime_type' => $uploaded['mime'],
        ]);

        return redirect()->route('gifs.show', $gif->proxy_path)
            ->with('success', 'GIF uploaded successfully!');
    }

    public function show(string $proxyPath)
    {
        $gif = Gif::where('proxy_path', $proxyPath)->firstOrFail();

        $gif->increment('views');

        $related = Gif::where('id', '!=', $gif->id)
            ->latest()
            ->limit(12)
            ->get();

        return view('gifs.show', compact('gif', 'related'));
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        if (empty($query)) {
            return redirect()->route('home');
        }

        $gifs = Gif::search($query)->latest()->paginate(24);

        return view('gifs.search', compact('gifs', 'query'));
    }
}
