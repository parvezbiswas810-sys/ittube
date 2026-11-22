<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\VideoResource;
use App\Models\Channel;
use App\Models\Subscription;
use App\Services\VideoService;
use App\Support\ApiResponse;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChannelController extends Controller
{
    public function index(Request $request)
    {
        $channels = Channel::query()
            ->with('category')
            ->where('status', 'active')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.$request->string('q').'%')->orWhere('handle', 'like', '%'.$request->string('q').'%')))
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            ->orderByDesc('subscribers_count')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::success(ChannelResource::collection($channels->getCollection()), 'Channels loaded', 200, [
            'current_page' => $channels->currentPage(),
            'per_page' => $channels->perPage(),
            'total' => $channels->total(),
            'last_page' => $channels->lastPage(),
            'has_more' => $channels->hasMorePages(),
        ]);
    }

    public function show(Request $request, string $handle)
    {
        $channel = Channel::with(['category', 'links'])
            ->where('handle', ltrim($handle, '@'))
            ->first();

        if (! $channel) {
            return ApiResponse::error('Channel not found.', [], 404);
        }

        $user = $request->user('sanctum');
        if ($user) {
            $channel->is_subscribed = Subscription::where('subscriber_id', $user->id)->where('channel_id', $channel->id)->exists();
        }

        $videos = $channel->videos()
            ->with(['channel', 'category'])
            ->where('type', 'video')
            ->when($user && $user->id === $channel->user_id, fn ($q) => $q, fn ($q) => $q->published())
            ->orderByDesc('published_at')
            ->limit(24)
            ->get();

        $shorts = $channel->videos()
            ->with(['channel', 'category'])
            ->where('type', 'short')
            ->when($user && $user->id === $channel->user_id, fn ($q) => $q, fn ($q) => $q->published())
            ->orderByDesc('published_at')
            ->limit(12)
            ->get();

        VideoService::attachUserState($videos->merge($shorts), $user);

        return ApiResponse::success([
            'channel' => new ChannelResource($channel),
            'videos' => VideoResource::collection($videos),
            'shorts' => VideoResource::collection($shorts),
        ]);
    }

    public function mine(Request $request)
    {
        $channel = $request->user()->channel()->with(['category', 'links'])->first();

        return $channel
            ? ApiResponse::success(new ChannelResource($channel))
            : ApiResponse::error('You do not have a channel yet.', [], 404);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->channel()->exists()) {
            return ApiResponse::error('You already have a channel.', [], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'handle' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.]+$/', 'unique:channels,handle'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $data['avatar'] = Media::storeImage($request->file('avatar'), 'channels/avatars');
        $data['banner'] = Media::storeImage($request->file('banner'), 'channels/banners');

        $channel = Channel::create($data + ['user_id' => $user->id, 'status' => 'active']);
        $user->forceFill(['role' => 'creator'])->save();

        return ApiResponse::success(new ChannelResource($channel->load('category')), 'Channel created successfully.', 201);
    }

    public function update(Request $request, int $id)
    {
        $channel = Channel::find($id);
        if (! $channel) {
            return ApiResponse::error('Channel not found.', [], 404);
        }

        if ($channel->user_id !== $request->user()->id) {
            return ApiResponse::error('You can only edit your own channel.', [], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'handle' => ['sometimes', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.]+$/', Rule::unique('channels', 'handle')->ignore($channel->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'links' => ['nullable', 'array', 'max:8'],
            'links.*.platform' => ['required_with:links', 'string', 'max:40'],
            'links.*.url' => ['required_with:links', 'url', 'max:500'],
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = Media::storeImage($request->file('avatar'), 'channels/avatars', $channel->avatar);
        }
        if ($request->hasFile('banner')) {
            $data['banner'] = Media::storeImage($request->file('banner'), 'channels/banners', $channel->banner);
        }

        $links = $data['links'] ?? null;
        unset($data['links']);

        $channel->fill($data)->save();

        if (is_array($links)) {
            $channel->links()->delete();
            foreach ($links as $link) {
                $channel->links()->create([
                    'platform' => Str::limit($link['platform'], 40, ''),
                    'url' => $link['url'],
                ]);
            }
        }

        return ApiResponse::success(new ChannelResource($channel->fresh(['category', 'links'])), 'Channel updated successfully.');
    }
}
