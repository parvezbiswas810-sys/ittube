@extends('admin.layout')
@section('title', 'Activity logs')
@section('page_title', 'Activity logs')

@section('content')
    <div class="page-head">
        <div><h1>Admin activity</h1><p>Every moderation and settings change is recorded here.</p></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>Audit trail</h3>
            <form class="filters" method="GET">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search action or description">
                <select name="admin">
                    <option value="">All admins</option>
                    @foreach ($admins as $admin)
                        <option value="{{ $admin->id }}" @selected((string) request('admin') === (string) $admin->id)>{{ $admin->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Action</th><th>Description</th><th>Admin</th><th>Target</th><th>IP</th><th>Time</th></tr></thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td><span class="pill pill-blue">{{ $log->action }}</span></td>
                        <td class="cell-sub">{{ $log->description }}</td>
                        <td class="cell-sub">{{ $log->admin?->name ?? 'System' }}</td>
                        <td class="cell-sub">{{ $log->target_type ? $log->target_type.' #'.$log->target_id : '—' }}</td>
                        <td class="cell-sub mono">{{ $log->ip_address }}</td>
                        <td class="cell-sub">{{ $log->created_at->format('M j, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><h4>No activity yet</h4><p>Admin actions will be logged here.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $logs])
    </div>
@endsection
