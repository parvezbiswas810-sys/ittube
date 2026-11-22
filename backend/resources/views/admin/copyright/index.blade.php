@extends('admin.layout')
@section('title', 'Copyright')
@section('page_title', 'Copyright')

@section('content')
    <div class="page-head">
        <div><h1>Copyright management</h1><p>Reports from users and rights holders. Evidence files are stored privately and are not publicly reachable.</p></div>
    </div>

    <div class="grid grid-4" style="margin-bottom:16px">
        @foreach ($counts as $label => $count)
            <div class="kpi"><div class="cell-sub">{{ ucfirst($label) }}</div><div class="cell-title" style="font-size:24px">{{ $count }}</div></div>
        @endforeach
    </div>

    <form class="filters" method="GET" style="margin-bottom:12px">
        <input class="search" type="text" name="q" value="{{ request('q') }}" placeholder="Rights holder, email or description">
        <select name="status">
            <option value="">Open cases</option>
            @foreach (['pending', 'review', 'approved', 'rejected', 'disputed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="type">
            <option value="">All types</option>
            @foreach (['video', 'short', 'audio', 'channel'] as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        <button class="btn btn-soft" type="submit">Filter</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Content</th><th>Rights holder</th><th>Reason</th><th>Status</th><th>Received</th><th></th></tr></thead>
                <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td class="mono">{{ $report->id }}</td>
                        <td>
                            <div class="cell-title">{{ ucfirst($report->content_type) }} #{{ $report->content_id ?: '—' }}</div>
                            <div class="cell-sub">{{ \Illuminate\Support\Str::limit($report->content_url, 48) ?: 'no URL' }}</div>
                        </td>
                        <td>
                            <div class="cell-title">{{ $report->rights_holder }}</div>
                            <div class="cell-sub">{{ $report->contact_email }}</div>
                        </td>
                        <td>{{ ucwords(str_replace('_', ' ', $report->reason)) }}</td>
                        <td>
                            <span class="pill {{ in_array($report->status, ['approved']) ? 'pill-red' : ($report->status === 'rejected' ? 'pill-gray' : ($report->status === 'disputed' ? 'pill-amber' : 'pill-blue')) }}">{{ ucfirst($report->status) }}</span>
                        </td>
                        <td class="cell-sub">{{ $report->created_at?->diffForHumans() }}</td>
                        <td><a class="btn btn-sm btn-soft" href="{{ route('admin.copyright.show', $report) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty">No copyright reports in this view.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">@include('admin.partials.pagination', ['paginator' => $reports])</div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-head"><h3>Matching foundation</h3><span class="pill pill-amber">Not active</span></div>
        <div class="card-pad">
            <p class="hint">The <span class="mono">copyright_matches</span> table and reference model exist so an audio/video fingerprinting provider can be connected later. No fingerprinting runs today, and nothing is flagged automatically.</p>
        </div>
    </div>
@endsection
