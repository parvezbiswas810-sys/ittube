@extends('admin.layout')
@section('title', 'Channels')
@section('page_title', 'Channel management')

@section('content')
    <div class="page-head">
        <div><h1>Channels</h1><p>{{ number_format($channels->total()) }} creator channels.</p></div>
    </div>
    <div class="card">
        <div class="card-head">
            <h3>All channels</h3>
            <form class="filters" method="GET">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or handle">
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (['active', 'suspended'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Channel</th><th>Owner</th><th>Videos</th><th>Subscribers</th><th>Status</th><th>Featured</th><th></th></tr></thead>
                <tbody>
                @forelse ($channels as $channel)
                    <tr>
                        <td>
                            <div class="row-user">
                                @if ($channel->avatar_url)
                                    <img src="{{ $channel->avatar_url }}" class="avatar" alt="" style="object-fit:cover">
                                @else
                                    <span class="avatar">{{ strtoupper(substr($channel->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <div class="cell-title">{{ $channel->name }} @if($channel->is_verified)<span class="pill pill-blue" style="margin-left:4px">Verified</span>@endif</div>
                                    <div class="cell-sub">@{{ $channel->handle }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="cell-sub">{{ $channel->user?->username }}</td>
                        <td class="cell-sub">{{ $channel->videos_count }}</td>
                        <td class="cell-sub">{{ number_format($channel->subscribers_count) }}</td>
                        <td><span class="pill {{ $channel->status === 'active' ? 'pill-green' : 'pill-red' }}">{{ ucfirst($channel->status) }}</span></td>
                        <td><span class="pill {{ $channel->is_featured ? 'pill-blue' : 'pill-gray' }}">{{ $channel->is_featured ? 'Featured' : '—' }}</span></td>
                        <td><a class="btn btn-ghost btn-sm" href="{{ route('admin.channels.show', $channel) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty"><h4>No channels yet</h4><p>Channels are created when users register or upload.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $channels])
    </div>
@endsection
