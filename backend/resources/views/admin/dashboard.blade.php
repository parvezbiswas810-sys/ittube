@extends('admin.layout')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Welcome back, {{ explode(' ', auth('admin')->user()->name)[0] }}</h1>
            <p>Here is what is happening across IT TUBE today.</p>
        </div>
        <div class="spacer"></div>
        <a href="{{ route('admin.videos.index', ['status' => 'pending']) }}" class="btn btn-ghost">Review pending videos</a>
        <a href="{{ route('admin.settings.index') }}" class="btn btn-primary">App settings</a>
    </div>

    <div class="grid grid-4">
        @php
            $cards = [
                ['label' => 'Total users', 'value' => $stats['users'], 'foot' => $stats['new_users'].' new this week', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8z'],
                ['label' => 'Videos', 'value' => $stats['videos'], 'foot' => $stats['shorts'].' shorts · '.$stats['pending'].' pending', 'icon' => 'M23 7l-7 5 7 5V7zM14 5H3a2 2 0 00-2 2v10a2 2 0 002 2h11a2 2 0 002-2V7a2 2 0 00-2-2z'],
                ['label' => 'Total views', 'value' => number_format($stats['views']), 'foot' => $stats['subscriptions'].' subscriptions', 'icon' => 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8zM12 15a3 3 0 100-6 3 3 0 000 6z'],
                ['label' => 'Pending reports', 'value' => $stats['reports'], 'foot' => $stats['comments'].' comments total', 'icon' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z'],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="card kpi">
                <div class="kpi-top">
                    <span class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $card['icon'] }}"/></svg></span>
                    {{ $card['label'] }}
                </div>
                <div class="kpi-value">{{ $card['value'] }}</div>
                <div class="kpi-foot">{{ $card['foot'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-2" style="margin-top:16px">
        <div class="card">
            <div class="card-head"><h3>User growth</h3><span class="pill pill-blue">Last 14 days</span></div>
            <div class="card-pad"><canvas id="chartUsers" height="130"></canvas></div>
        </div>
        <div class="card">
            <div class="card-head"><h3>Views activity</h3><span class="pill pill-navy">Daily</span></div>
            <div class="card-pad"><canvas id="chartViews" height="130"></canvas></div>
        </div>
    </div>

    <div class="split" style="margin-top:16px">
        <div class="grid" style="gap:16px">
            <div class="card">
                <div class="card-head">
                    <h3>Latest videos</h3>
                    <a href="{{ route('admin.videos.index') }}" class="btn btn-soft btn-sm">View all</a>
                </div>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Video</th><th>Channel</th><th>Status</th><th>Views</th></tr></thead>
                        <tbody>
                        @forelse ($latestVideos as $video)
                            <tr>
                                <td>
                                    <div class="row-user">
                                        @if ($video->thumbnail_url)
                                            <img src="{{ $video->thumbnail_url }}" class="thumb" alt="">
                                        @endif
                                        <div>
                                            <a class="cell-title" href="{{ route('admin.videos.show', $video) }}">{{ $video->title }}</a>
                                            <div class="cell-sub">{{ $video->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="cell-sub">{{ $video->channel?->name }}</td>
                                <td><span class="pill {{ $video->status === 'published' ? 'pill-green' : ($video->status === 'pending' ? 'pill-amber' : 'pill-gray') }}">{{ ucfirst($video->status) }}</span></td>
                                <td class="cell-sub">{{ number_format($video->views_count) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">No videos uploaded yet. Uploads will appear here.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>Category performance</h3></div>
                <div class="card-pad">
                    @forelse ($categoryBreakdown as $category)
                        @php($max = max(1, $categoryBreakdown->max('videos_count')))
                        <div style="margin-bottom:12px">
                            <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:5px">
                                <b style="color:var(--navy)">{{ $category->name }}</b>
                                <span style="color:var(--muted)">{{ $category->videos_count }} videos</span>
                            </div>
                            <div style="height:7px;background:var(--light);border-radius:99px;overflow:hidden">
                                <div style="height:100%;width:{{ round($category->videos_count / $max * 100) }}%;background:linear-gradient(90deg,var(--primary),var(--cyan));border-radius:99px"></div>
                            </div>
                        </div>
                    @empty
                        <div class="empty">No categories yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid" style="gap:16px">
            <div class="card">
                <div class="card-head"><h3>Pending reports</h3><a href="{{ route('admin.reports.index') }}" class="btn btn-soft btn-sm">Open</a></div>
                @forelse ($pendingReports as $report)
                    <div class="list-row">
                        <span class="pill pill-red">{{ $report->reason }}</span>
                        <div class="grow">
                            <div style="font-weight:600;color:var(--navy);font-size:12.5px">{{ class_basename($report->reportable_type) }} #{{ $report->reportable_id }}</div>
                            <div class="cell-sub">{{ $report->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty">No pending reports.</div>
                @endforelse
            </div>

            <div class="card">
                <div class="card-head"><h3>New users</h3><a href="{{ route('admin.users.index') }}" class="btn btn-soft btn-sm">All users</a></div>
                @forelse ($newUsers as $user)
                    <div class="list-row">
                        <span class="avatar">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
                        <div class="grow">
                            <div style="font-weight:600;color:var(--navy);font-size:12.5px">{{ $user->username }}</div>
                            <div class="cell-sub">{{ $user->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="pill pill-gray">{{ ucfirst($user->status) }}</span>
                    </div>
                @empty
                    <div class="empty">No users registered yet.</div>
                @endforelse
            </div>

            <div class="card">
                <div class="card-head"><h3>Recent admin activity</h3></div>
                @forelse ($recentActivity as $log)
                    <div class="list-row">
                        <div class="grow">
                            <div style="font-weight:600;color:var(--navy);font-size:12.5px">{{ $log->description ?: $log->action }}</div>
                            <div class="cell-sub">{{ $log->admin?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty">No activity logged yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
window.addEventListener('load', function () {
    if (typeof Chart === 'undefined') return;
    const labels = @json($days->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M j')));
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = '#64748B';
    new Chart(document.getElementById('chartUsers'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'New users',
                data: @json($userGrowth),
                borderColor: '#0866F5',
                backgroundColor: (ctx) => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 220);
                    g.addColorStop(0, 'rgba(8,102,245,.22)');
                    g.addColorStop(1, 'rgba(8,102,245,0)');
                    return g;
                },
                fill: true,
                tension: .38,
                borderWidth: 2.5,
                pointRadius: 0,
                pointHoverRadius: 4
            }]
        },
        options: {plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true, ticks: {precision: 0}, grid: {color: '#EEF3FA'}}, x: {grid: {display: false}}}}
    });
    new Chart(document.getElementById('chartViews'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Views',
                data: @json($viewsGrowth),
                backgroundColor: 'rgba(0,199,242,.55)',
                hoverBackgroundColor: '#0866F5',
                borderRadius: 6,
                maxBarThickness: 18
            }]
        },
        options: {plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true, ticks: {precision: 0}, grid: {color: '#EEF3FA'}}, x: {grid: {display: false}}}}
    });
});
</script>
@endpush
