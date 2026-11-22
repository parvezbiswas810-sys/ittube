@extends('admin.layout')
@section('title', 'Copyright case')
@section('page_title', 'Copyright case')

@section('content')
    <div class="page-head">
        <div>
            <h1>Case #{{ $report->id }}</h1>
            <p>{{ ucfirst($report->content_type) }} #{{ $report->content_id ?: '—' }} · received {{ $report->created_at?->format('d M Y H:i') }}</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('admin.copyright.index') }}">Back to list</a>
    </div>

    <div class="split" style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px">
        <div class="card">
            <div class="card-head"><h3>Report</h3><span class="pill pill-blue">{{ ucfirst($report->status) }}</span></div>
            <div class="card-pad">
                <div class="grid grid-2" style="gap:12px">
                    <div class="field"><label>Rights holder</label><div class="cell-title">{{ $report->rights_holder }}</div></div>
                    <div class="field"><label>Reason</label><div class="cell-title">{{ ucwords(str_replace('_', ' ', $report->reason)) }}</div></div>
                    <div class="field"><label>Contact</label><div class="cell-title">{{ $report->contact_name ?: '—' }}</div><div class="cell-sub">{{ $report->contact_email }}</div></div>
                    <div class="field"><label>Reporter account</label><div class="cell-title">{{ $report->reporter?->username ?: 'Guest submission' }}</div></div>
                    <div class="field" style="grid-column:1/-1"><label>Content URL</label><div class="mono">{{ $report->content_url ?: '—' }}</div></div>
                    <div class="field" style="grid-column:1/-1"><label>Description</label><div>{{ $report->description ?: '—' }}</div></div>
                    <div class="field" style="grid-column:1/-1">
                        <label>Evidence</label>
                        @if ($report->evidence_path)
                            <div class="mono">Stored privately: {{ $report->evidence_path }}</div>
                            <div class="hint">Download from the server filesystem (storage/app/private) — it is deliberately not web-accessible.</div>
                        @else
                            <div class="hint">No evidence attached.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <form class="card" method="POST" action="{{ route('admin.copyright.resolve', $report) }}">
            @csrf
            <div class="card-head"><h3>Decision</h3></div>
            <div class="card-pad">
                <div class="field">
                    <label>Case status</label>
                    <select name="status">
                        @foreach (['pending', 'review', 'approved', 'rejected', 'disputed'] as $status)
                            <option value="{{ $status }}" @selected($report->status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Action on content</label>
                    <select name="action">
                        <option value="none">No content change</option>
                        <option value="restrict">Restrict video (hidden from public)</option>
                        <option value="remove">Remove video</option>
                        <option value="suspend_account">Suspend the uploader</option>
                    </select>
                </div>
                <div class="field">
                    <label>Internal note</label>
                    <textarea name="admin_note" rows="4">{{ $report->admin_note }}</textarea>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Save decision</button>
                @if ($report->reviewed_at)
                    <div class="hint" style="margin-top:8px">Last reviewed {{ $report->reviewed_at->diffForHumans() }} by {{ $report->reviewer?->name ?: 'admin' }}.</div>
                @endif
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-head"><h3>Case history for this content</h3></div>
        <div class="card-pad">
            @forelse ($related as $other)
                <div class="list-row">
                    <div>
                        <div class="cell-title">#{{ $other->id }} · {{ ucwords(str_replace('_', ' ', $other->reason)) }}</div>
                        <div class="cell-sub">{{ $other->rights_holder }} · {{ $other->created_at?->format('d M Y') }}</div>
                    </div>
                    <span class="pill pill-gray">{{ ucfirst($other->status) }}</span>
                </div>
            @empty
                <div class="empty">This is the first report for this content.</div>
            @endforelse
        </div>
    </div>
@endsection
