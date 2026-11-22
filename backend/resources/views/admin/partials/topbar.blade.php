@php($admin = auth('admin')->user())
@php($pendingReportsCount = $pendingReportsCount ?? \App\Models\Report::where('status', 'pending')->count())
<header class="topbar">
    <button class="icon-btn" type="button" data-sidebar-toggle aria-label="Toggle menu" style="display:none">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <div class="page-title">@yield('page_title', 'Dashboard')</div>
    <form class="search" action="{{ route('admin.users.index') }}" method="GET">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input type="search" name="q" placeholder="Search users, email..." value="{{ request('q') }}">
    </form>
    <a class="icon-btn" href="{{ route('admin.reports.index') }}" title="Pending reports">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
        @if (($pendingReportsCount ?? 0) > 0)<span class="badge-dot"></span>@endif
    </a>
    <a class="admin-chip" href="{{ route('admin.profile.index') }}">
        <span class="avatar">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
        <span class="admin-meta"><b>{{ $admin->name }}</b><span>{{ $admin->role?->name ?? 'Administrator' }}</span></span>
    </a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button class="btn btn-ghost btn-sm" type="submit">Log out</button>
    </form>
</header>
