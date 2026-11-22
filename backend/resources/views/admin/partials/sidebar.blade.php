@php
    $admin = auth('admin')->user();
    $nav = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'perm' => 'dashboard.view', 'icon' => 'M3 12l9-8 9 8v8a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z'],
        ['route' => 'admin.users.index', 'label' => 'Users', 'perm' => 'users.manage', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75'],
        ['route' => 'admin.channels.index', 'label' => 'Channels', 'perm' => 'channels.manage', 'icon' => 'M3 7h18v12H3zM8 7V4h8v3'],
        ['route' => 'admin.videos.index', 'label' => 'Videos', 'perm' => 'videos.manage', 'icon' => 'M23 7l-7 5 7 5V7zM14 5H3a2 2 0 00-2 2v10a2 2 0 002 2h11a2 2 0 002-2V7a2 2 0 00-2-2z'],
        ['route' => 'admin.shorts.index', 'label' => 'Shorts', 'perm' => 'shorts.manage', 'icon' => 'M9 18V6l10 6-10 6z'],
        ['route' => 'admin.categories.index', 'label' => 'Categories', 'perm' => 'categories.manage', 'icon' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z'],
        ['route' => 'admin.comments.index', 'label' => 'Comments', 'perm' => 'comments.manage', 'icon' => 'M21 11.5a8.4 8.4 0 01-9 8.4 9 9 0 01-4-.9L3 21l1.9-4.9a8.4 8.4 0 019-13.2 8.4 8.4 0 017.1 8.6z'],
        ['route' => 'admin.reports.index', 'label' => 'Reports', 'perm' => 'reports.manage', 'icon' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z'],
        ['route' => 'admin.notifications.index', 'label' => 'Notifications', 'perm' => 'notifications.manage', 'icon' => 'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0'],
        ['route' => 'admin.home.index', 'label' => 'Home Page', 'perm' => 'home.manage', 'icon' => 'M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z'],
        ['route' => 'admin.auth.index', 'label' => 'Authentication', 'perm' => 'auth.manage', 'icon' => 'M15 7a4 4 0 10-4 4m0 0v6m0-6h4M3 11h6'],
        ['route' => 'admin.live.index', 'label' => 'Live Streams', 'perm' => 'live.manage', 'icon' => 'M23 7l-7 5 7 5V7zM14 5H3a2 2 0 00-2 2v10a2 2 0 002 2h11a2 2 0 002-2V7a2 2 0 00-2-2z'],
        ['route' => 'admin.copyright.index', 'label' => 'Copyright', 'perm' => 'copyright.manage', 'icon' => 'M9 12l2 2 4-4M12 3l8 4v6c0 5-3.4 8.7-8 10-4.6-1.3-8-5-8-10V7l8-4z'],
        ['route' => 'admin.platform.index', 'label' => 'Platform', 'perm' => 'platform.manage', 'icon' => 'M12 2v20M2 12h20M4.9 4.9l14.2 14.2M19.1 4.9L4.9 19.1'],
        ['route' => 'admin.settings.index', 'label' => 'App Settings', 'perm' => 'settings.manage', 'icon' => 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1-1.6 1.7 1.7 0 00-1.9.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.6-1 1.7 1.7 0 00-.3-1.9l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.9.3H10a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.9-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.9V10a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z'],
        ['route' => 'admin.roles.index', 'label' => 'Admin Roles', 'perm' => 'roles.manage', 'icon' => 'M12 2l8 4v6c0 5-3.4 8.7-8 10-4.6-1.3-8-5-8-10V6l8-4z'],
        ['route' => 'admin.logs.index', 'label' => 'Activity Logs', 'perm' => 'logs.view', 'icon' => 'M12 8v4l3 2M12 22a10 10 0 110-20 10 10 0 010 20z'],
    ];
@endphp
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <img src="{{ asset('assets/logo.png') }}" alt="IT TUBE">
        <div>
            <div class="brand-name">IT TUBE</div>
            <div class="brand-sub">Nexdi Code Lab</div>
        </div>
    </div>
    <div class="nav">
        <div class="nav-label">Workspace</div>
        @foreach ($nav as $item)
            @if (! $item['perm'] || $admin->hasPermission($item['perm']))
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route'].'*') ? 'active' : '' }}">
                    <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $item['icon'] }}"/></svg></span>
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
        <div class="nav-label">System</div>
        <a href="{{ route('admin.profile.index') }}" class="{{ request()->routeIs('admin.profile*') ? 'active' : '' }}">
            <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            My Account
        </a>
        <a href="{{ config('app.url') }}" target="_blank" rel="noopener">
            <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><path d="M15 3h6v6M10 14L21 3"/></svg></span>
            Support
        </a>
    </div>
    <div class="sidebar-foot">
        IT TUBE Admin v{{ \App\Services\SettingService::get('app_version', '1.0.0') }}<br>
        Blue Orbit Edition
    </div>
</aside>
