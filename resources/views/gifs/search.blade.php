@extends('layouts.app')

@section('title', 'Search: ' . $query)

@section('content')
    <h1 class="page-title">Search results for: "{{ $query }}"</h1>

    @if($gifs->count() > 0)
        <p style="color:#888;margin-bottom:18px;">Found {{ $gifs->total() }} GIF{{ $gifs->total() !== 1 ? 's' : '' }}</p>
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
            {{ $gifs->appends(['q' => $query])->links() }}
        </div>
    @else
        <div class="empty-state">
            <h2>No results found</h2>
            <p>Try different keywords.</p>
        </div>
    @endif
@endsection
