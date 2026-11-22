<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Video;

class NotificationService
{
    public static function send(int|User $user, string $type, string $title, ?string $body = null, array $data = []): ?Notification
    {
        $user = $user instanceof User ? $user : User::find($user);
        if (! $user) {
            return null;
        }

        $preferenceColumn = [
            'new_subscriber' => 'new_subscriber',
            'new_upload' => 'new_upload',
            'comment_reply' => 'comment_reply',
            'channel_activity' => 'channel_activity',
            'admin_announcement' => 'admin_announcement',
        ][$type] ?? null;

        if ($preferenceColumn) {
            $preferences = NotificationPreference::firstOrCreate(['user_id' => $user->id]);
            if (! $preferences->{$preferenceColumn}) {
                return null;
            }
        }

        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    public static function broadcast(string $title, ?string $body = null, array $data = []): int
    {
        return Notification::create([
            'user_id' => null,
            'type' => 'admin_announcement',
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]) ? User::query()->where('status', 'active')->count() : 0;
    }

    public static function notifySubscribers(Channel $channel, Video $video): void
    {
        $channel->subscriptions()->with('subscriber')->chunkById(200, function ($subscriptions) use ($channel, $video) {
            foreach ($subscriptions as $subscription) {
                if ($subscription->subscriber) {
                    static::send($subscription->subscriber, 'new_upload', $channel->name.' uploaded a new video', $video->title, [
                        'video_id' => $video->id,
                        'channel_handle' => $channel->handle,
                    ]);
                }
            }
        });
    }
}
