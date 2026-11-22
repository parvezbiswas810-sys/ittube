@extends('admin.layout')
@section('title', 'Platform')
@section('page_title', 'Platform')

@section('content')
    <div class="page-head">
        <div><h1>Platform integrations</h1><p>Streaming, downloads, advertising and monetization. Everything here is configuration and eligibility — no impressions, earnings or viewers are invented.</p></div>
    </div>

    <form method="POST" action="{{ route('admin.platform.update') }}">
        @csrf @method('PUT')

        <div class="card" style="margin-bottom:16px">
            <div class="card-head"><h3>Live streaming provider</h3><span class="pill {{ $state['stream_provider'] && $state['stream_rtmp_url'] ? 'pill-green' : 'pill-amber' }}">{{ $state['stream_provider'] && $state['stream_rtmp_url'] ? 'Configured' : 'Not configured' }}</span></div>
            <div class="card-pad">
                <div class="grid grid-2" style="gap:16px">
                    <div class="field">
                        <label>Provider name</label>
                        <input type="text" name="stream_provider" value="{{ $state['stream_provider'] }}" placeholder="nginx-rtmp / mux / cloudflare">
                    </div>
                    <div class="field">
                        <label>RTMP ingest URL</label>
                        <input type="text" name="stream_rtmp_url" value="{{ $state['stream_rtmp_url'] }}" placeholder="rtmp://live.example.com/app">
                    </div>
                    <div class="field">
                        <label>HLS playback base</label>
                        <input type="text" name="stream_playback_base" value="{{ $state['stream_playback_base'] }}" placeholder="https://live.example.com/hls">
                        <div class="hint">Playback URL per stream is issued by the provider; the API never fabricates one.</div>
                    </div>
                    <div class="field">
                        <label>Provider API key</label>
                        <input type="password" name="stream_provider_key" value="" autocomplete="new-password"
                               placeholder="{{ $state['has_provider_key'] ? '•••••••• stored — leave blank to keep' : 'Optional' }}">
                        <div class="hint">Encrypted. Never exposed to the app or the API.</div>
                    </div>
                    <div class="field">
                        <label>Live streaming</label>
                        <label class="switch">
                            <input type="hidden" name="bool_fields[]" value="live_stream_enabled">
                            <input type="checkbox" name="live_stream_enabled" value="1" @checked($state['live_stream_enabled'])>
                            Enable live streaming
                        </label>
                    </div>
                    <div class="field">
                        <label>Downloads</label>
                        <label class="switch">
                            <input type="hidden" name="bool_fields[]" value="downloads_enabled">
                            <input type="checkbox" name="downloads_enabled" value="1" @checked($state['downloads_enabled'])>
                            Allow video downloads
                        </label>
                        <div class="hint">Creators can still disable downloads per video.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-head"><h3>Advertising foundation</h3><span class="pill pill-navy">No ad server connected</span></div>
            <div class="card-pad">
                <div class="grid grid-2" style="gap:16px">
                    <div class="field">
                        <label>Ad provider</label>
                        <input type="text" name="ads_provider" value="{{ $state['ads_provider'] }}" placeholder="vast / adsense / custom">
                    </div>
                    <div class="field">
                        <label>Client tag / account</label>
                        <input type="text" name="ads_client_tag" value="{{ $state['ads_client_tag'] }}" placeholder="ca-pub-... or VAST endpoint">
                    </div>
                    <div class="field">
                        <label>Ads enabled</label>
                        <label class="switch">
                            <input type="hidden" name="bool_fields[]" value="ads_enabled">
                            <input type="checkbox" name="ads_enabled" value="1" @checked($state['ads_enabled'])>
                            Serve ad placements
                        </label>
                        <div class="hint">The player has pre/mid/post-roll slots; nothing plays until a placement has an active tag.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-head"><h3>Monetization foundation</h3><span class="pill pill-navy">Eligibility only</span></div>
            <div class="card-pad">
                <div class="grid grid-2" style="gap:16px">
                    <div class="field">
                        <label>Minimum subscribers</label>
                        <input type="number" name="monetization_min_subscribers" value="{{ $state['monetization_min_subscribers'] }}" min="0">
                    </div>
                    <div class="field">
                        <label>Minimum views</label>
                        <input type="number" name="monetization_min_views" value="{{ $state['monetization_min_views'] }}" min="0">
                    </div>
                    <div class="field">
                        <label>Monetization</label>
                        <label class="switch">
                            <input type="hidden" name="bool_fields[]" value="monetization_enabled">
                            <input type="checkbox" name="monetization_enabled" value="1" @checked($state['monetization_enabled'])>
                            Enable the monetization programme
                        </label>
                        <div class="hint">No revenue is calculated and no payouts are made in this phase.</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-bottom:20px">
            <button class="btn btn-primary" type="submit">Save platform configuration</button>
        </div>
    </form>

    <div class="card" style="margin-bottom:16px">
        <div class="card-head"><h3>Ad placements</h3><span class="pill pill-blue">{{ $placements->count() }} slots</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Position</th><th>Offset</th><th>Tag</th><th>Active</th><th></th></tr></thead>
                <tbody>
                @forelse ($placements as $placement)
                    <tr>
                        <td class="cell-title">{{ $placement->name }}</td>
                        <td>{{ ucfirst($placement->position) }}</td>
                        <td class="cell-sub">{{ $placement->offset_seconds }}s</td>
                        <td class="cell-sub mono">{{ \Illuminate\Support\Str::limit($placement->ad_tag_url, 34) ?: '—' }}</td>
                        <td><span class="pill {{ $placement->is_active ? 'pill-green' : 'pill-gray' }}">{{ $placement->is_active ? 'On' : 'Off' }}</span></td>
                        <td style="display:flex;gap:6px">
                            <form method="POST" action="{{ route('admin.platform.ads.toggle', $placement) }}">@csrf
                                <button class="btn btn-sm btn-soft" type="submit">{{ $placement->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.platform.ads.destroy', $placement) }}" onsubmit="return confirm('Delete this ad placement?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty">No ad placements. Add one below when you have an ad tag.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <form class="card-pad" method="POST" action="{{ route('admin.platform.ads.store') }}">
            @csrf
            <div class="grid grid-4" style="gap:10px">
                <div class="field"><label>Name</label><input type="text" name="name" required placeholder="Pre-roll default"></div>
                <div class="field"><label>Position</label>
                    <select name="position">
                        <option value="preroll">Pre-roll</option>
                        <option value="midroll">Mid-roll</option>
                        <option value="postroll">Post-roll</option>
                    </select>
                </div>
                <div class="field"><label>Offset (seconds)</label><input type="number" name="offset_seconds" value="0" min="0"></div>
                <div class="field"><label>VAST tag URL</label><input type="url" name="ad_tag_url" placeholder="https://ads.example.com/vast.xml"></div>
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:10px"><button class="btn btn-soft" type="submit">Add placement</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-head"><h3>Creator monetization</h3><span class="pill pill-gray">{{ $monetized->count() }} profiles</span></div>
        <div class="card-pad">
            @forelse ($monetized as $profile)
                <form class="list-row" method="POST" action="{{ route('admin.platform.monetization.update', $profile) }}">
                    @csrf
                    <div>
                        <div class="cell-title">{{ $profile->user?->username ?: 'User #'.$profile->user_id }}</div>
                        <div class="cell-sub">Updated {{ $profile->updated_at?->diffForHumans() }}</div>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center">
                        <select name="status" style="min-width:130px">
                            @foreach (['ineligible', 'review', 'eligible', 'enabled', 'suspended'] as $status)
                                <option value="{{ $status }}" @selected($profile->status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <label class="switch"><input type="checkbox" name="ads_enabled" value="1" @checked($profile->ads_enabled)> Ads</label>
                        <button class="btn btn-sm btn-soft" type="submit">Save</button>
                    </div>
                </form>
            @empty
                <div class="empty">No creator has applied for monetization yet. Eligibility is never granted automatically.</div>
            @endforelse
        </div>
    </div>
@endsection
