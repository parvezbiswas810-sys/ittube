<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use Illuminate\Http\Request;

class LiveStreamController extends Controller
{
    public function index(Request $request)
    {
        $streams = LiveStream::with(['channel', 'user'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $configured = (bool) SettingService::get('stream_provider') && (bool) SettingService::get('stream_rtmp_url');

        return view('admin.live.index', [
            'streams' => $streams,
            'configured' => $configured,
            'enabled' => SettingService::bool('live_stream_enabled'),
            'provider' => SettingService::get('stream_provider'),
        ]);
    }

    public function update(Request $request, LiveStream $stream)
    {
        $data = $request->validate([
            'status' => ['required', 'in:scheduled,preparing,live,ended,failed,cancelled'],
        ]);

        $stream->update([
            'status' => $data['status'],
            'started_at' => $data['status'] === 'live' ? ($stream->started_at ?? now()) : $stream->started_at,
            'ended_at' => in_array($data['status'], ['ended', 'failed', 'cancelled'], true) ? now() : $stream->ended_at,
        ]);

        ActivityLogger::log('live.status', 'Set live stream #'.$stream->id.' to '.$data['status'], $stream);

        return back()->with('status', 'Stream status updated.');
    }
}
