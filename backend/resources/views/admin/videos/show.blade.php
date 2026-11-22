@extends('admin.layout')
@section('title', 'Video · '.$video->title)
@section('page_title', 'Video details')

@section('content')
    <div class="page-head">
        <div><h1>{{ $video->title }}</h1><p>#{{ $video->id }} · {{ $video->channel?->name }} · {{ $video->created_at->format('M j, Y H:i') }}</p></div>
        <div class="spacer"></div>
        <a href="{{ route('admin.videos.index') }}" class="btn btn-ghost">Back to videos</a>
    </div>

    <div class="split">
        <div class="grid" style="gap:16px">
            <div class="card">
                <div class="card-head"><h3>Playback preview</h3><span class="pill pill-gray">{{ ucfirst($video->status) }}</span></div>
                <div class="card-pad">
                    @if ($video->playback_url)
                        <video controls preload="metadata" style="width:100%;border-radius:12px;background:#0A1260;aspect-ratio:16/9" poster="{{ $video->thumbnail_url }}">
                            <source src="{{ $video->playback_url }}" type="{{ $video->mime_type ?: 'video/mp4' }}">
                        </video>
                    @else
                        <div class="empty">No video file attached.</div>
                    @endif
                    <div class="grid grid-4" style="margin-top:16px">
                        <div><div class="cell-sub">Views</div><b>{{ number_format($video->views_count) }}</b></div>
                        <div><div class="cell-sub">Likes</div><b>{{ number_format($video->likes_count) }}</b></div>
                        <div><div class="cell-sub">Comments</div><b>{{ number_format($video->comments_count) }}</b></div>
                        <div><div class="cell-sub">Duration</div><b>{{ gmdate('H:i:s', $video->duration) }}</b></div>
                    </div>
                    <div class="stat-line" style="margin-top:12px"><span>Resolution</span><b>{{ $video->resolution ?: 'Unknown' }}</b></div>
                    <div class="stat-line"><span>File size</span><b>{{ $video->file_size ? number_format($video->file_size / 1048576, 1).' MB' : '—' }}</b></div>
                    <div class="stat-line"><span>Type</span><b>{{ ucfirst($video->type) }}</b></div>
                    <div class="stat-line"><span>Published</span><b>{{ $video->published_at?->format('M j, Y H:i') ?? 'Not published' }}</b></div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>Edit metadata</h3></div>
                <div class="card-pad">
                    <form method="POST" action="{{ route('admin.videos.update', $video) }}" class="form-grid" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="field">
                            <label>Title</label>
                            <input name="title" value="{{ old('title', $video->title) }}" required>
                        </div>
                        <div class="field">
                            <label>Description</label>
                            <textarea name="description">{{ old('description', $video->description) }}</textarea>
                        </div>
                        <div class="grid grid-3" style="gap:12px">
                            <div class="field">
                                <label>Category</label>
                                <select name="category_id">
                                    <option value="">None</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($video->category_id === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Status</label>
                                <select name="status">
                                    @foreach (\App\Models\Video::STATUSES as $status)
                                        <option value="{{ $status }}" @selected($video->status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Visibility</label>
                                <select name="visibility">
                                    @foreach (\App\Models\Video::VISIBILITIES as $visibility)
                                        <option value="{{ $visibility }}" @selected($video->visibility === $visibility)>{{ ucfirst($visibility) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label>Rejection reason (shown to creator)</label>
                            <input name="rejection_reason" value="{{ old('rejection_reason', $video->rejection_reason) }}">
                        </div>
                        <div class="field">
                            <label>Replace thumbnail</label>
                            <input type="file" name="thumbnail" accept="image/*">
                        </div>
                        <div><button class="btn btn-primary" type="submit">Save video</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid" style="gap:16px">
            <div class="card">
                <div class="card-head"><h3>Moderation</h3></div>
                <div class="card-pad">
                    <div class="stat-line"><span>Channel</span><b>{{ $video->channel?->name }}</b></div>
                    <div class="stat-line"><span>Owner</span><b>{{ $video->user?->username }}</b></div>
                    <div class="stat-line"><span>Featured</span><b>{{ $video->is_featured ? 'Yes' : 'No' }}</b></div>
                    <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
                        <form method="POST" action="{{ route('admin.videos.status', $video) }}">
                            @csrf
                            <input type="hidden" name="status" value="published">
                            <button class="btn btn-primary btn-sm" type="submit">Approve & publish</button>
                        </form>
                        <form method="POST" action="{{ route('admin.videos.feature', $video) }}">
                            @csrf
                            <button class="btn btn-soft btn-sm" type="submit">{{ $video->is_featured ? 'Unfeature' : 'Feature' }}</button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('admin.videos.destroy', $video) }}" data-confirm="Remove this video from the platform?" style="margin-top:10px">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block" type="submit">Remove video</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>Reports</h3></div>
                @forelse ($reports as $report)
                    <div class="list-row">
                        <span class="pill pill-red">{{ $report->reason }}</span>
                        <div class="grow"><div class="cell-sub">{{ $report->details ?: 'No details' }}</div></div>
                        <span class="pill pill-gray">{{ ucfirst($report->status) }}</span>
                    </div>
                @empty
                    <div class="empty">No reports on this video.</div>
                @endforelse
            </div>

            <div class="card">
                <div class="card-head"><h3>Tags</h3></div>
                <div class="card-pad" style="display:flex;gap:6px;flex-wrap:wrap">
                    @forelse ($video->tags as $tag)
                        <span class="pill pill-blue">#{{ $tag->name }}</span>
                    @empty
                        <span class="cell-sub">No tags.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
