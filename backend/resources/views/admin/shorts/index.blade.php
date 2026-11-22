@extends('admin.layout')
@section('title', 'Shorts')
@section('page_title', 'Shorts management')

@section('content')
    <div class="page-head">
        <div><h1>Shorts</h1><p>{{ number_format($videos->total()) }} vertical videos.</p></div>
        <div class="spacer"></div>
        <a href="{{ route('admin.videos.index') }}" class="btn btn-ghost">Back to videos</a>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>Short videos</h3>
            <form class="filters" method="GET">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search title">
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\Video::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
            </form>
        </div>
        <div class="card-pad">
            <div class="grid grid-4">
                @forelse ($videos as $video)
                    <div class="card" style="overflow:hidden">
                        <div style="aspect-ratio:9/14;background:var(--light);position:relative">
                            @if ($video->thumbnail_url)
                                <img src="{{ $video->thumbnail_url }}" alt="" style="width:100%;height:100%;object-fit:cover">
                            @else
                                <div style="height:100%;display:grid;place-items:center;color:#93A3C0;font-size:11px">No thumbnail</div>
                            @endif
                            <span class="pill {{ $video->status === 'published' ? 'pill-green' : 'pill-amber' }}" style="position:absolute;top:8px;left:8px">{{ ucfirst($video->status) }}</span>
                        </div>
                        <div style="padding:12px">
                            <div class="cell-title" style="white-space:normal">{{ $video->title }}</div>
                            <div class="cell-sub">{{ $video->channel?->name }} · {{ number_format($video->views_count) }} views</div>
                            <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap">
                                <form method="POST" action="{{ route('admin.videos.status', $video) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $video->status === 'published' ? 'suspended' : 'published' }}">
                                    <button class="btn btn-soft btn-sm" type="submit">{{ $video->status === 'published' ? 'Remove' : 'Approve' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.videos.feature', $video) }}">
                                    @csrf
                                    <button class="btn btn-ghost btn-sm" type="submit">{{ $video->is_featured ? 'Unfeature' : 'Feature' }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty" style="grid-column:1/-1"><h4>No shorts yet</h4><p>Shorts uploaded from the Android app will appear here.</p></div>
                @endforelse
            </div>
        </div>
        @include('admin.partials.pagination', ['paginator' => $videos])
    </div>
@endsection
