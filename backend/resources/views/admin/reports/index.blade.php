@extends('admin.layout')
@section('title', 'Reports')
@section('page_title', 'Report management')

@section('content')
    <div class="page-head">
        <div><h1>Reports</h1><p>Review user reports and take moderation action.</p></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>Report queue</h3>
            <form class="filters" method="GET">
                <select name="status">
                    @foreach (['pending', 'reviewed', 'dismissed', 'actioned'] as $status)
                        <option value="{{ $status }}" @selected(request('status', 'pending') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select name="type">
                    <option value="">All content</option>
                    @foreach (['Video', 'Comment', 'Channel', 'User'] as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Target</th><th>Reason</th><th>Reporter</th><th>Status</th><th>Reported</th><th>Action</th></tr></thead>
                <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>
                            <div class="cell-title">{{ class_basename($report->reportable_type) }} #{{ $report->reportable_id }}</div>
                            <div class="cell-sub">{{ \Illuminate\Support\Str::limit($report->details, 70) ?: 'No details provided' }}</div>
                        </td>
                        <td><span class="pill pill-red">{{ str_replace('_', ' ', $report->reason) }}</span></td>
                        <td class="cell-sub">{{ $report->reporter?->username ?? 'Guest' }}</td>
                        <td><span class="pill {{ $report->status === 'pending' ? 'pill-amber' : ($report->status === 'actioned' ? 'pill-red' : 'pill-green') }}">{{ ucfirst($report->status) }}</span></td>
                        <td class="cell-sub">{{ $report->created_at->diffForHumans() }}</td>
                        <td>
                            <button class="btn btn-ghost btn-sm" data-modal-open="resolve-{{ $report->id }}">Review</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><h4>Queue is clear</h4><p>No reports match this filter.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $reports])
    </div>

    @foreach ($reports as $report)
        <div class="modal" id="resolve-{{ $report->id }}">
            <div class="modal-box">
                <div class="modal-head"><h3>Review report #{{ $report->id }}</h3><button class="icon-btn" type="button" data-modal-close>✕</button></div>
                <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                    @csrf
                    <div class="modal-body form-grid">
                        <div class="alert alert-error" style="margin:0">
                            <div>
                                <b>{{ class_basename($report->reportable_type) }} #{{ $report->reportable_id }}</b><br>
                                Reason: {{ str_replace('_', ' ', $report->reason) }}<br>
                                {{ $report->details }}
                            </div>
                        </div>
                        <div class="field">
                            <label>Resolution status</label>
                            <select name="status">
                                @foreach (['reviewed', 'dismissed', 'actioned'] as $status)
                                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Action</label>
                            <select name="action">
                                <option value="none">No action</option>
                                <option value="remove_content">Remove content</option>
                                <option value="suspend_account">Suspend account</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Moderator note</label>
                            <textarea name="admin_note"></textarea>
                        </div>
                    </div>
                    <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save decision</button></div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
