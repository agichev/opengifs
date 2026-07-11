@extends('layouts.app')

@section('title', $gif->title ?? 'GIF')

@section('content')
    <div class="gif-single">
        <h1>{{ $gif->title ?? 'Untitled GIF' }}</h1>

        <img src="{{ route('gifs.proxy', $gif->proxy_path) }}" alt="{{ $gif->title ?? 'GIF' }}">

        <div class="gif-details">
            @if($gif->keywords)
                <div style="margin-bottom:10px;">
                    <strong>Keywords:</strong>
                    @foreach(explode(',', $gif->keywords) as $keyword)
                        <a href="{{ route('gifs.search', ['q' => trim($keyword)]) }}" style="display:inline-block;background:#e8f0fe;padding:2px 8px;border-radius:3px;margin:2px;font-size:12px;text-decoration:none;">{{ trim($keyword) }}</a>
                    @endforeach
                </div>
            @endif

            <div>👁️ {{ $gif->views }} views</div>
            <div>📅 {{ $gif->created_at->format('M d, Y H:i') }}</div>
            @if($gif->file_size > 0)
                <div>💾 {{ round($gif->file_size / 1024, 1) }} KB</div>
            @endif
        </div>

        <div class="direct-link-box">
            <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">Direct GIF URL (proxied):</label>
            <input type="text" value="{{ route('gifs.proxy', $gif->proxy_path) }}" readonly onclick="this.select()">
        </div>

        <div class="direct-link-box">
            <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">Page URL:</label>
            <input type="text" value="{{ route('gifs.show', $gif->proxy_path) }}" readonly onclick="this.select()">
        </div>

        <div class="direct-link-box" style="background:#fff8e0;border-color:#e8d8a0;">
            <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">HTML embed code:</label>
            <input type="text" value="<img src=&quot;{{ route('gifs.proxy', $gif->proxy_path) }}&quot; alt=&quot;{{ $gif->title ?? 'GIF' }}&quot;>" readonly onclick="this.select()">
        </div>

        <div class="direct-link-box" style="background:#e8f8f0;border-color:#a0d8b0;">
            <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">Markdown embed code:</label>
            <input type="text" value="![{{ $gif->title ?? 'GIF' }}]({{ route('gifs.proxy', $gif->proxy_path) }})" readonly onclick="this.select()">
        </div>

        <div class="direct-link-box" style="background:#f8f0e8;border-color:#d8c0a0;">
            <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">BBCode embed (forums):</label>
            <input type="text" value="[img]{{ route('gifs.proxy', $gif->proxy_path) }}[/img]" readonly onclick="this.select()">
        </div>
    </div>

    @if($related->count() > 0)
        <h2 class="section-title">More GIFs</h2>
        <div class="gif-grid">
            @foreach($related as $gif)
                <a href="{{ route('gifs.show', $gif->proxy_path) }}" class="gif-card" style="text-decoration:none;color:inherit;">
                    <img src="{{ route('gifs.proxy', $gif->proxy_path) }}" alt="{{ $gif->title ?? 'GIF' }}" loading="lazy">
                    <div class="gif-info">
                        <div class="gif-title">{{ $gif->title ?? 'Untitled' }}</div>
                        <div class="gif-meta">{{ $gif->views }} views</div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
