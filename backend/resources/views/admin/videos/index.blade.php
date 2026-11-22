@extends('admin.layout')
@section('title', 'Videos')
@section('page_title', 'Video management')

@section('content')
    <div class="page-head">
        <div><h1>Videos</h1><p>{{ number_format($videos->total()) }} long-form videos.</p></div>
        <div class="spacer"></div>
        <a href="{{ route('admin.shorts.index') }}" class="btn btn-ghost">Manage shorts</a>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>Video library</h3>
            <form class="filters" method="GET">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search title">
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\Video::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select name="category">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Video</th><th>Channel</th><th>Category</th><th>Status</th><th>Visibility</th><th>Views</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($videos as $video)
                    <tr>
                        <td>
                            <div class="row-user">
                                @if ($video->thumbnail_url)
                                    <img src="{{ $video->thumbnail_url }}" class="thumb" alt="">
                                @else
                                    <div class="thumb" style="display:grid;place-items:center;color:#93A3C0;font-size:10px">No image</div>
                                @endif
                                <div>
                                    <a class="cell-title" href="{{ route('admin.videos.show', $video) }}">{{ $video->title }}</a>
                                    <div class="cell-sub">{{ $video->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="cell-sub">{{ $video->channel?->name }}</td>
                        <td class="cell-sub">{{ $video->category?->name ?? '—' }}</td>
                        <td><span class="pill {{ $video->status === 'published' ? 'pill-green' : ($video->status === 'pending' ? 'pill-amber' : 'pill-gray') }}">{{ ucfirst($video->status) }}</span></td>
                        <td class="cell-sub">{{ ucfirst($video->visibility) }}</td>
                        <td class="cell-sub">{{ number_format($video->views_count) }}</td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                @if ($video->status === 'pending')
                                    <form method="POST" action="{{ route('admin.videos.status', $video) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="published">
                                        <button class="btn btn-soft btn-sm" type="submit">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.videos.status', $video) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="btn btn-danger btn-sm" type="submit">Reject</button>
                                    </form>
                                @else
                                    <a class="btn btn-ghost btn-sm" href="{{ route('admin.videos.show', $video) }}">Manage</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty"><h4>No videos found</h4><p>Uploads from the Android app will appear here.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $videos])
    </div>
@endsection
