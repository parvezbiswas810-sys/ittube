<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\VideoResource;
use App\Models\Channel;
use App\Models\Subscription;
use App\Services\NotificationService;
use App\Services\VideoService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Counters;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $channels = Channel::query()
            ->with('category')
            ->whereIn('id', Subscription::where('subscriber_id', $request->user()->id)->select('channel_id'))
            ->orderByDesc('subscribers_count')
            ->paginate((int) $request->input('per_page', 20));

        $channels->getCollection()->each(fn ($channel) => $channel->is_subscribed = true);

        return ApiResponse::success(ChannelResource::collection($channels->getCollection()), 'Subscriptions loaded', 200, [
            'current_page' => $channels->currentPage(),
            'per_page' => $channels->perPage(),
            'total' => $channels->total(),
            'last_page' => $channels->lastPage(),
            'has_more' => $channels->hasMorePages(),
        ]);
    }

    public function feed(Request $request)
    {
        $user = $request->user();
        $channelIds = Subscription::where('subscriber_id', $user->id)->pluck('channel_id');

        $videos = \App\Models\Video::query()
            ->with(['channel', 'category'])
            ->whereIn('channel_id', $channelIds)
            ->published()
            ->orderByDesc('published_at')
            ->paginate((int) $request->input('per_page', 15));

        VideoService::attachUserState(collect($videos->items()), $user);

        return ApiResponse::success(VideoResource::collection($videos->getCollection()), 'Subscription feed', 200, [
            'current_page' => $videos->currentPage(),
            'per_page' => $videos->perPage(),
            'total' => $videos->total(),
            'last_page' => $videos->lastPage(),
            'has_more' => $videos->hasMorePages(),
        ]);
    }

    public function subscribe(Request $request, int $channelId)
    {
        $channel = Channel::active()->find($channelId);
        if (! $channel) {
            return ApiResponse::error('Channel not found.', [], 404);
        }

        if ($channel->user_id === $request->user()->id) {
            return ApiResponse::error('You cannot subscribe to your own channel.', [], 422);
        }

        $created = DB::transaction(function () use ($channel, $request) {
            $subscription = Subscription::firstOrCreate([
                'subscriber_id' => $request->user()->id,
                'channel_id' => $channel->id,
            ]);

            if ($subscription->wasRecentlyCreated) {
                $channel->increment('subscribers_count');
            }

            return $subscription->wasRecentlyCreated;
        });

        if ($created) {
            NotificationService::send($channel->user_id, 'new_subscriber', $request->user()->displayName().' subscribed to your channel', null, [
                'channel_handle' => $channel->handle,
            ]);
        }

        return ApiResponse::success([
            'subscribed' => true,
            'subscribers_count' => $channel->fresh()->subscribers_count,
        ], $created ? 'Subscribed successfully.' : 'Already subscribed.');
    }

    public function unsubscribe(Request $request, int $channelId)
    {
        $channel = Channel::find($channelId);
        if (! $channel) {
            return ApiResponse::error('Channel not found.', [], 404);
        }

        DB::transaction(function () use ($channel, $request) {
            $deleted = Subscription::where('subscriber_id', $request->user()->id)->where('channel_id', $channel->id)->delete();
            if ($deleted) {
                Counters::decrement($channel, 'subscribers_count');
            }
        });

        return ApiResponse::success([
            'subscribed' => false,
            'subscribers_count' => $channel->fresh()->subscribers_count,
        ], 'Unsubscribed successfully.');
    }
}
