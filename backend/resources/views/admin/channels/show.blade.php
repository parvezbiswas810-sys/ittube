@extends('admin.layout')
@section('title', 'Channel · @'.$channel->handle)
@section('page_title', 'Channel details')

@section('content')
    <div class="page-head">
        <div><h1>{{ $channel->name }}</h1><p>@{{ $channel->handle }} · {{ number_format($channel->subscribers_count) }} subscribers</p></div>
        <div class="spacer"></div>
        <a href="{{ route('admin.channels.index') }}" class="btn btn-ghost">Back to channels</a>
    </div>

    <div class="split">
        <div class="grid" style="gap:16px">
            <div class="card">
                <div class="card-head"><h3>Channel settings</h3></div>
                <div class="card-pad">
                    <form method="POST" action="{{ route('admin.channels.update', $channel) }}" class="form-grid">
                        @csrf @method('PUT')
                        <div class="grid grid-2" style="gap:14px">
                            <div class="field">
                                <label>Channel name</label>
                                <input name="name" value="{{ old('name', $channel->name) }}" required>
                            </div>
                            <div class="field">
                                <label>Handle</label>
                                <input name="handle" value="{{ old('handle', $channel->handle) }}" required>
                            </div>
                            <div class="field">
                                <label>Category</label>
                                <select name="category_id">
                                    <option value="">No category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($channel->category_id === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Badges</label>
                                <label class="switch" style="margin-bottom:6px"><input type="checkbox" name="is_verified" value="1" @checked($channel->is_verified)> Verified channel</label>
                                <label class="switch"><input type="checkbox" name="is_featured" value="1" @checked($channel->is_featured)> Featured channel</label>
                            </div>
                        </div>
                        <div class="field">
                            <label>Description</label>
                            <textarea name="description">{{ old('description', $channel->description) }}</textarea>
                        </div>
                        <div><button class="btn btn-primary" type="submit">Save channel</button></div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>Channel videos</h3></div>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Views</th></tr></thead>
                        <tbody>
                        @forelse ($videos as $video)
                            <tr>
                                <td><a class="cell-title" href="{{ route('admin.videos.show', $video) }}">{{ $video->title }}</a></td>
                                <td><span class="pill pill-navy">{{ ucfirst($video->type) }}</span></td>
                                <td><span class="pill pill-blue">{{ ucfirst($video->status) }}</span></td>
                                <td class="cell-sub">{{ number_format($video->views_count) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">No videos in this channel yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid" style="gap:16px">
            <div class="card">
                <div class="card-head"><h3>Channel overview</h3></div>
                <div class="card-pad">
                    @if ($channel->banner_url)
                        <img src="{{ $channel->banner_url }}" class="thumb-lg" alt="" style="margin-bottom:12px">
                    @endif
                    <div class="stat-line"><span>Owner</span><b>{{ $channel->user?->username }}</b></div>
                    <div class="stat-line"><span>Videos</span><b>{{ number_format($channel->videos_count) }}</b></div>
                    <div class="stat-line"><span>Total views</span><b>{{ number_format($channel->total_views) }}</b></div>
                    <div class="stat-line"><span>Created</span><b>{{ $channel->created_at->format('M j, Y') }}</b></div>
                    <div class="stat-line"><span>Status</span><b>{{ ucfirst($channel->status) }}</b></div>
                    <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
                        <form method="POST" action="{{ route('admin.channels.status', $channel) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $channel->status === 'active' ? 'suspended' : 'active' }}">
                            <button class="btn btn-ghost btn-sm" type="submit">{{ $channel->status === 'active' ? 'Suspend' : 'Restore' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.channels.feature', $channel) }}">
                            @csrf
                            <button class="btn btn-soft btn-sm" type="submit">{{ $channel->is_featured ? 'Unfeature' : 'Feature' }}</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-head"><h3>Social links</h3></div>
                @forelse ($channel->links as $link)
                    <div class="list-row">
                        <span class="pill pill-blue">{{ $link->platform }}</span>
                        <div class="grow cell-sub" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $link->url }}</div>
                    </div>
                @empty
                    <div class="empty">No links added.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
