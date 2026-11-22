@extends('admin.layout')
@section('title', 'My account')
@section('page_title', 'My account')

@section('content')
    <div class="page-head">
        <div><h1>{{ $admin->name }}</h1><p>{{ $admin->email }} · {{ $admin->role?->name ?? 'No role' }}</p></div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head"><h3>Profile</h3></div>
            <div class="card-pad">
                <form method="POST" action="{{ route('admin.profile.update') }}" class="form-grid">
                    @csrf @method('PUT')
                    <div class="field"><label>Name</label><input name="name" value="{{ old('name', $admin->name) }}" required></div>
                    <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $admin->email) }}" required></div>
                    <button class="btn btn-primary" type="submit">Save profile</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Change password</h3></div>
            <div class="card-pad">
                <form method="POST" action="{{ route('admin.profile.password') }}" class="form-grid">
                    @csrf @method('PUT')
                    <div class="field"><label>Current password</label><input type="password" name="current_password" required></div>
                    <div class="field"><label>New password</label><input type="password" name="password" required minlength="8"></div>
                    <div class="field"><label>Confirm new password</label><input type="password" name="password_confirmation" required minlength="8"></div>
                    <button class="btn btn-primary" type="submit">Update password</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-head"><h3>Session</h3></div>
        <div class="card-pad" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <div class="cell-sub">Last login {{ $admin->last_login_at?->diffForHumans() ?? 'never' }} from {{ $admin->last_login_ip ?? 'unknown IP' }}</div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn btn-ghost" type="submit">Log out of admin</button>
            </form>
        </div>
    </div>
@endsection
