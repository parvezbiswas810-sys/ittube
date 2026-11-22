@extends('admin.layout')
@section('title', 'Comments')
@section('page_title', 'Comment moderation')

@section('content')
    <div class="page-head">
        <div><h1>Comments</h1><p>{{ number_format($comments->total()) }} comments across the platform.</p></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>All comments</h3>
            <form class="filters" method="GET">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search comment text">
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (['published', 'pending', 'removed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <label class="switch"><input type="checkbox" name="reported" value="1" @checked(request('reported'))> Reported only</label>
                <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Comment</th><th>User</th><th>Video</th><th>Status</th><th>Posted</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($comments as $comment)
                    <tr>
                        <td><div class="cell-title" style="white-space:normal;max-width:380px">{{ \Illuminate\Support\Str::limit($comment->body, 140) }}</div></td>
                        <td class="cell-sub">{{ $comment->user?->username ?? 'Deleted user' }}</td>
                        <td class="cell-sub">
                            @if ($comment->video)
                                <a href="{{ route('admin.videos.show', $comment->video) }}">{{ \Illuminate\Support\Str::limit($comment->video->title, 34) }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td><span class="pill {{ $comment->status === 'published' ? 'pill-green' : ($comment->status === 'pending' ? 'pill-amber' : 'pill-red') }}">{{ ucfirst($comment->status) }}</span></td>
                        <td class="cell-sub">{{ $comment->created_at->diffForHumans() }}</td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <form method="POST" action="{{ route('admin.comments.status', $comment) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $comment->status === 'removed' ? 'published' : 'removed' }}">
                                    <button class="btn btn-ghost btn-sm" type="submit">{{ $comment->status === 'removed' ? 'Restore' : 'Remove' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}" data-confirm="Delete this comment permanently?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><h4>No comments</h4><p>Comments from the Android app appear here.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $comments])
    </div>
@endsection
