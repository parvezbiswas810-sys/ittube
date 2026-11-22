@extends('admin.layout')
@section('title', 'Notifications')
@section('page_title', 'Notification management')

@section('content')
    <div class="page-head">
        <div><h1>Notifications</h1><p>Send announcements and review delivered notifications.</p></div>
        <div class="spacer"></div>
        <button class="btn btn-primary" data-modal-open="modal-send">New announcement</button>
    </div>

    <div class="card">
        <div class="card-head"><h3>Sent notifications</h3></div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Title</th><th>Audience</th><th>Type</th><th>Sent</th><th></th></tr></thead>
                <tbody>
                @forelse ($notifications as $notification)
                    <tr>
                        <td>
                            <div class="cell-title">{{ $notification->title }}</div>
                            <div class="cell-sub">{{ \Illuminate\Support\Str::limit($notification->body, 80) }}</div>
                        </td>
                        <td class="cell-sub">{{ $notification->user?->username ?? 'All users' }}</td>
                        <td><span class="pill pill-blue">{{ str_replace('_', ' ', $notification->type) }}</span></td>
                        <td class="cell-sub">{{ $notification->created_at->diffForHumans() }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.notifications.destroy', $notification) }}" data-confirm="Delete this notification?">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty"><h4>No notifications yet</h4><p>Send your first announcement.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $notifications])
    </div>

    <div class="modal" id="modal-send">
        <div class="modal-box">
            <div class="modal-head"><h3>Send announcement</h3><button class="icon-btn" type="button" data-modal-close>✕</button></div>
            <form method="POST" action="{{ route('admin.notifications.store') }}">
                @csrf
                <div class="modal-body form-grid">
                    <div class="field"><label>Title</label><input name="title" required maxlength="160"></div>
                    <div class="field"><label>Message</label><textarea name="body"></textarea></div>
                    <div class="field">
                        <label>Audience</label>
                        <select name="target">
                            <option value="all">All active users</option>
                            <option value="selected">Selected users</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Selected users (Ctrl/Cmd click for multiple)</label>
                        <select name="user_ids[]" multiple size="6">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->username }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Send</button></div>
            </form>
        </div>
    </div>
@endsection
