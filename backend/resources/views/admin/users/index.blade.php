@extends('admin.layout')
@section('title', 'Users')
@section('page_title', 'User management')

@section('content')
    <div class="page-head">
        <div><h1>Users</h1><p>{{ number_format($users->total()) }} registered accounts across IT TUBE.</p></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>All users</h3>
            <form class="filters" method="GET">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search username or email">
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (['active', 'suspended', 'banned'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select name="role">
                    <option value="">All roles</option>
                    @foreach (['user', 'creator'] as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Channel</th><th>Joined</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="row-user">
                                @if ($user->profile?->avatar_url)
                                    <img src="{{ $user->profile->avatar_url }}" class="avatar" alt="" style="object-fit:cover">
                                @else
                                    <span class="avatar">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <div class="cell-title">{{ $user->username }}</div>
                                    <div class="cell-sub">{{ $user->profile?->display_name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="cell-sub">{{ $user->email }}</td>
                        <td><span class="pill pill-blue">{{ ucfirst($user->role) }}</span></td>
                        <td>
                            <span class="pill {{ $user->status === 'active' ? 'pill-green' : ($user->status === 'suspended' ? 'pill-amber' : 'pill-red') }}">{{ ucfirst($user->status) }}</span>
                        </td>
                        <td class="cell-sub">{{ $user->channel ? '@'.$user->channel->handle : '—' }}</td>
                        <td class="cell-sub">{{ $user->created_at->format('M j, Y') }}</td>
                        <td><a class="btn btn-ghost btn-sm" href="{{ route('admin.users.show', $user) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty"><h4>No users found</h4><p>Try changing your filters.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $users])
    </div>
@endsection
