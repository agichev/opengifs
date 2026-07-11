@extends('layouts.app')

@section('title', 'Browse GIFs')

@section('content')
    <h1 class="page-title">Latest GIFs</h1>

    @if($gifs->count() > 0)
        <div class="gif-grid">
            @foreach($gifs as $gif)
                <a href="{{ route('gifs.show', $gif->proxy_path) }}" class="gif-card" style="text-decoration:none;color:inherit;">
                    <img src="{{ route('gifs.proxy', $gif->proxy_path) }}" alt="{{ $gif->title ?? 'GIF' }}" loading="lazy">
                    <div class="gif-info">
                        <div class="gif-title">{{ $gif->title ?? 'Untitled' }}</div>
                        <div class="gif-meta">{{ $gif->views }} views</div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="pagination">
            {{ $gifs->links() }}
        </div>
    @else
        <div class="empty-state">
            <h2>No GIFs yet</h2>
            <p>Be the first to upload a GIF!</p>
            <br>
            <a href="{{ route('gifs.create') }}" style="display:inline-block;padding:12px 28px;background:linear-gradient(to bottom,#4a90d9,#357abd);border:2px solid #2a5f9e;border-radius:6px;color:#fff;font-weight:bold;text-decoration:none;text-shadow:1px 1px 0 #2a5f9e;">Upload a GIF</a>
        </div>
    @endif
@endsection
