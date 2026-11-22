@extends('admin.layout')
@section('title', 'Live streams')
@section('page_title', 'Live streams')

@section('content')
    <div class="page-head">
        <div><h1>Live streams</h1><p>Creators publish RTMP from OBS to the configured provider; viewers watch HLS. Shared cPanel hosting cannot run an RTMP server itself.</p></div>
        <a class="btn btn-soft" href="{{ route('admin.platform.index') }}">Streaming configuration</a>
    </div>

    @if (! $configured)
        <div class="alert alert-error" style="margin-bottom:16px">
            <div>
                <b>No streaming provider configured.</b> Live streaming is unavailable for creators until you add an RTMP ingest URL and playback base under
                <a href="{{ route('admin.platform.index') }}">Platform</a>. Nothing is faked: the API returns 503 for stream creation until then.
            </div>
        </div>
    @elseif (! $enabled)
        <div class="alert alert-error" style="margin-bottom:16px"><div><b>Provider configured, feature disabled.</b> Turn on “Live streaming” under Platform to let creators go live.</div></div>
    @else
        <div class="alert alert-success" style="margin-bottom:16px"><div><b>Live streaming is active</b> via {{ $provider }}.</div></div>
    @endif

    <form class="filters" method="GET" style="margin-bottom:12px">
        <input class="search" type="text" name="q" value="{{ request('q') }}" placeholder="Search title">
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['scheduled', 'preparing', 'live', 'ended', 'failed', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button class="btn btn-soft" type="submit">Filter</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Stream</th><th>Creator</th><th>Schedule</th><th>Status</th><th>Manage</th></tr></thead>
                <tbody>
                @forelse ($streams as $stream)
                    <tr>
                        <td>
                            <div class="cell-title">{{ $stream->title }}</div>
                            <div class="cell-sub">{{ $stream->game_title ?: '—' }} · {{ $stream->visibility }}</div>
                        </td>
                        <td>
                            <div class="cell-title">{{ $stream->channel?->name ?: $stream->user?->username }}</div>
                            <div class="cell-sub mono">#{{ $stream->id }}</div>
                        </td>
                        <td class="cell-sub">{{ $stream->scheduled_for?->format('d M Y H:i') ?: 'not scheduled' }}</td>
                        <td><span class="pill {{ $stream->status === 'live' ? 'pill-red' : ($stream->status === 'ended' ? 'pill-gray' : 'pill-blue') }}">{{ ucfirst($stream->status) }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.live.update', $stream) }}" style="display:flex;gap:6px">
                                @csrf
                                <select name="status" style="min-width:120px">
                                    @foreach (['scheduled', 'preparing', 'live', 'ended', 'failed', 'cancelled'] as $status)
                                        <option value="{{ $status }}" @selected($stream->status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-soft" type="submit">Apply</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty">No live streams yet.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">@include('admin.partials.pagination', ['paginator' => $streams])</div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-head"><h3>Infrastructure notes</h3></div>
        <div class="card-pad">
            <p class="hint">Supported pattern: an RTMP ingest + HLS playback provider (for example a self-hosted nginx-rtmp, Mux, Cloudflare Stream or an equivalent service). Stream keys are stored on the stream row, hidden from every public API response, and only returned to the channel owner through <span class="mono">/api/v1/live-streams/{id}/credentials</span>.</p>
        </div>
    </div>
@endsection
