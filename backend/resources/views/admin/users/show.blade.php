@extends('admin.layout')
@section('title', 'User · '.$user->username)
@section('page_title', 'User details')

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $user->username }}</h1>
            <p>{{ $user->email }} · Joined {{ $user->created_at->format('M j, Y') }}</p>
        </div>
        <div class="spacer"></div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Back to users</a>
    </div>

    <div class="split">
        <div class="grid" style="gap:16px">
            <div class="card">
                <div class="card-head"><h3>Account information</h3></div>
                <div class="card-pad">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="form-grid" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="grid grid-2" style="gap:14px">
                            <div class="field">
                                <label>Username</label>
                                <input value="{{ $user->username }}" disabled>
                                <div class="hint">Usernames are immutable to protect links.</div>
                            </div>
                            <div class="field">
                                <label>Email</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                            </div>
                            <div class="field">
                                <label>Display name</label>
                                <input name="display_name" value="{{ old('display_name', $user->profile?->display_name) }}">
                            </div>
                            <div class="field">
                                <label>Role</label>
                                <select name="role">
                                    @foreach (['user', 'creator'] as $role)
                                        <option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label>Bio</label>
                            <textarea name="bio">{{ old('bio', $user->profile?->bio) }}</textarea>
                        </div>
                        <div class="field">
                            <label>Profile photo</label>
                            <input type="file" name="avatar" accept="image/*">
                        </div>
                        <div><button class="btn btn-primary" type="submit">Save changes</button></div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>Recent uploads</h3><span class="pill pill-gray">{{ $user->videos->count() }} shown</span></div>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Title</th><th>Status</th><th>Views</th><th>Uploaded</th></tr></thead>
                        <tbody>
                        @forelse ($user->videos as $video)
                            <tr>
                                <td><a class="cell-title" href="{{ route('admin.videos.show', $video) }}">{{ $video->title }}</a></td>
                                <td><span class="pill pill-blue">{{ ucfirst($video->status) }}</span></td>
                                <td class="cell-sub">{{ number_format($video->views_count) }}</td>
                                <td class="cell-sub">{{ $video->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">This user has not uploaded videos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid" style="gap:16px">
            <div class="card">
                <div class="card-head"><h3>Account status</h3></div>
                <div class="card-pad">
                    <div class="stat-line"><span>Current status</span><b>{{ ucfirst($user->status) }}</b></div>
                    <div class="stat-line"><span>Channel</span><b>{{ $user->channel?->name ?? 'None' }}</b></div>
                    <div class="stat-line"><span>Email verified</span><b>{{ $user->email_verified_at ? 'Yes' : 'No' }}</b></div>
                    <div class="stat-line"><span>Last login</span><b>{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</b></div>
                    @if ($user->suspension_reason)
                        <div class="alert alert-error" style="margin-top:12px">{{ $user->suspension_reason }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.users.status', $user) }}" class="form-grid" style="margin-top:14px">
                        @csrf
                        <div class="field">
                            <label>Set status</label>
                            <select name="status">
                                @foreach (['active', 'suspended', 'banned'] as $status)
                                    <option value="{{ $status }}" @selected($user->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Reason (optional)</label>
                            <input name="reason" placeholder="Reason for suspension or ban">
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Update status</button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Delete this user permanently? Their content will be removed." style="margin-top:10px">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block" type="submit">Delete user</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>Reports against user</h3></div>
                @forelse ($reports as $report)
                    <div class="list-row">
                        <span class="pill pill-red">{{ $report->reason }}</span>
                        <div class="grow">
                            <div class="cell-sub">{{ $report->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="pill pill-gray">{{ ucfirst($report->status) }}</span>
                    </div>
                @empty
                    <div class="empty">No reports for this user.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
