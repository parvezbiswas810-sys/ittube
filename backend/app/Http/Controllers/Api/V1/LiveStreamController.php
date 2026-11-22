<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LiveChatMessage;
use App\Models\LiveStream;
use App\Services\SettingService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LiveStreamController extends Controller
{
    public function index()
    {
        if (! SettingService::bool('live_stream_enabled')) {
            return ApiResponse::success(['streams' => [], 'enabled' => false, 'reason' => 'Live streaming is not enabled.']);
        }

        $streams = LiveStream::with('channel')
            ->whereIn('status', ['scheduled', 'live'])
            ->where('visibility', 'public')
            ->orderByRaw("FIELD(status, 'live', 'scheduled')")
            ->orderBy('scheduled_for')
            ->paginate(20);

        return ApiResponse::success($streams->through(fn ($s) => $this->publicPayload($s)));
    }

    public function show(int $id)
    {
        $stream = LiveStream::with('channel')->find($id);
        if (! $stream) {
            return ApiResponse::error('Live stream not found.', [], 404);
        }

        return ApiResponse::success($this->publicPayload($stream, true));
    }

    public function mine(Request $request)
    {
        $streams = LiveStream::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success($streams->through(fn ($s) => $this->publicPayload($s, true)));
    }

    public function store(Request $request)
    {
        $guard = $this->guard();
        if ($guard) {
            return $guard;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'game_title' => ['nullable', 'string', 'max:120'],
            'visibility' => ['nullable', 'in:public,unlisted,private'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();
        $channel = $user->channel;

        if (! $channel) {
            return ApiResponse::error('Create a channel before starting a live stream.', [], 403);
        }

        $thumbnail = $request->hasFile('thumbnail')
            ? \App\Support\Media::storeImage($request->file('thumbnail'), 'live')
            : null;

        $stream = LiveStream::create([
            'user_id' => $user->id,
            'channel_id' => $channel->id,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'thumbnail' => $thumbnail,
            'status' => empty($data['scheduled_for']) ? 'preparing' : 'scheduled',
            'visibility' => $data['visibility'] ?? 'public',
            'provider' => SettingService::get('stream_provider'),
            'game_title' => $data['game_title'] ?? null,
            'scheduled_for' => $data['scheduled_for'] ?? null,
        ]);

        return ApiResponse::success([
            'stream' => $this->publicPayload($stream, true),
            'ingest' => $this->ingestPayload($stream),
        ], 'Live stream created.', 201);
    }

    public function update(Request $request, int $id)
    {
        $stream = LiveStream::find($id);
        if (! $stream) {
            return ApiResponse::error('Live stream not found.', [], 404);
        }

        if ($stream->user_id !== $request->user()->id) {
            return ApiResponse::error('You cannot edit this stream.', [], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'game_title' => ['nullable', 'string', 'max:120'],
            'visibility' => ['sometimes', 'in:public,unlisted,private'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $stream->update($data);

        return ApiResponse::success($this->publicPayload($stream->fresh(), true), 'Live stream updated.');
    }

    public function credentials(Request $request, int $id)
    {
        $stream = LiveStream::find($id);
        if (! $stream) {
            return ApiResponse::error('Live stream not found.', [], 404);
        }

        if ($stream->user_id !== $request->user()->id) {
            return ApiResponse::error('You cannot access this stream configuration.', [], 403);
        }

        return ApiResponse::success($this->ingestPayload($stream));
    }

    public function start(Request $request, int $id)
    {
        $stream = $this->owned($request, $id);
        if ($stream instanceof \Illuminate\Http\JsonResponse) {
            return $stream;
        }

        if (! $stream->playback_url) {
            return ApiResponse::error('The streaming provider has not issued a playback URL yet.', [], 409);
        }

        $stream->update(['status' => 'live', 'started_at' => now()]);

        return ApiResponse::success($this->publicPayload($stream->fresh(), true), 'Stream is live.');
    }

    public function end(Request $request, int $id)
    {
        $stream = $this->owned($request, $id);
        if ($stream instanceof \Illuminate\Http\JsonResponse) {
            return $stream;
        }

        $stream->update(['status' => 'ended', 'ended_at' => now()]);

        return ApiResponse::success($this->publicPayload($stream->fresh(), true), 'Stream ended.');
    }

    public function chat(int $id)
    {
        $stream = LiveStream::find($id);
        if (! $stream) {
            return ApiResponse::error('Live stream not found.', [], 404);
        }

        $messages = LiveChatMessage::where('live_stream_id', $id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => [
                'id' => $m->id,
                'author' => $m->author_name ?: 'Guest',
                'body' => $m->body,
                'created_at' => $m->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success($messages);
    }

    public function sendChat(Request $request, int $id)
    {
        $stream = LiveStream::find($id);
        if (! $stream || $stream->status !== 'live') {
            return ApiResponse::error('Chat is only available while the stream is live.', [], 409);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:500'],
        ]);

        $user = $request->user();
        $message = LiveChatMessage::create([
            'live_stream_id' => $id,
            'user_id' => $user->id,
            'author_name' => $user->profile?->display_name ?: $user->username,
            'body' => $data['body'],
        ]);

        return ApiResponse::success([
            'id' => $message->id,
            'author' => $message->author_name,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
        ], 'Message sent.', 201);
    }

    protected function guard(): ?\Illuminate\Http\JsonResponse
    {
        if (! SettingService::bool('live_stream_enabled')) {
            return ApiResponse::error('Live streaming is currently disabled.', [], 403);
        }

        if (! SettingService::get('stream_provider') || ! SettingService::get('stream_rtmp_url')) {
            return ApiResponse::error(
                'Live streaming is not configured yet. An administrator must connect an RTMP provider.',
                ['streaming' => ['No streaming provider is configured.']],
                503
            );
        }

        return null;
    }

    protected function owned(Request $request, int $id): LiveStream|\Illuminate\Http\JsonResponse
    {
        $stream = LiveStream::find($id);

        if (! $stream) {
            return ApiResponse::error('Live stream not found.', [], 404);
        }

        if ($stream->user_id !== $request->user()->id) {
            return ApiResponse::error('You cannot manage this stream.', [], 403);
        }

        return $stream;
    }

    protected function publicPayload(LiveStream $stream, bool $withPlayback = false): array
    {
        $payload = [
            'id' => $stream->id,
            'title' => $stream->title,
            'description' => $stream->description,
            'status' => $stream->status,
            'visibility' => $stream->visibility,
            'thumbnail_url' => \App\Support\Media::url($stream->thumbnail),
            'scheduled_for' => $stream->scheduled_for?->toIso8601String(),
            'started_at' => $stream->started_at?->toIso8601String(),
            'ended_at' => $stream->ended_at?->toIso8601String(),
            'game_title' => $stream->game_title,
            'channel' => $stream->channel ? [
                'id' => $stream->channel->id,
                'name' => $stream->channel->name,
                'handle' => $stream->channel->handle,
            ] : null,
        ];

        if ($withPlayback) {
            $payload['playback_url'] = $stream->status === 'live' ? $stream->playback_url : null;
            $payload['hls_ready'] = (bool) $stream->playback_url;
        }

        return $payload;
    }

    protected function ingestPayload(LiveStream $stream): array
    {
        if (! SettingService::get('stream_provider') || ! SettingService::get('stream_rtmp_url')) {
            return ['available' => false, 'message' => 'No streaming provider is configured.'];
        }

        if (! $stream->stream_key) {
            $stream->forceFill(['stream_key' => Str::random(40)])->save();
        }

        return [
            'available' => true,
            'provider' => $stream->provider,
            'rtmp_url' => $stream->rtmp_url ?: SettingService::get('stream_rtmp_url'),
            'stream_key' => $stream->stream_key,
            'playback_url' => $stream->playback_url,
            'note' => 'Paste the RTMP URL and stream key into OBS. Playback is HLS from the provider.',
        ];
    }
}
