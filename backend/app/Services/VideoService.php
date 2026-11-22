<?php

namespace App\Services;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoReaction;
use App\Models\VideoView;
use App\Support\Counters;
use Illuminate\Support\Facades\DB;

class VideoService
{
    /**
     * Persist an uploaded file (direct or assembled from chunks) as a video row.
     *
     * @return array{video: Video, ffmpeg_available: bool}
     */
    public static function createFromUpload(
        User $user,
        \Illuminate\Http\UploadedFile $file,
        array $data,
        ?\Illuminate\Http\UploadedFile $thumbnail = null,
        ?UploadService $uploadService = null,
    ): array {
        $uploadService ??= app(UploadService::class);

        $stored = $uploadService->storeVideo($file);

        if ($thumbnail !== null) {
            $stored['thumbnail'] = $uploadService->extractThumbnail($thumbnail);
        }

        $status = SettingService::bool('upload_approval_required') ? 'pending' : 'published';

        $video = DB::transaction(function () use ($user, $data, $stored, $status) {
            $channel = $user->channel()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->displayName(),
                    'handle' => \Illuminate\Support\Str::slug($user->username) ?: 'creator'.$user->id,
                    'status' => 'active',
                ]
            );

            $video = Video::create([
                'user_id' => $user->id,
                'channel_id' => $channel->id,
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'video',
                'thumbnail' => $stored['thumbnail'],
                'video_path' => $stored['video_path'],
                'mime_type' => $stored['mime_type'],
                'duration' => $stored['duration'],
                'file_size' => $stored['file_size'],
                'resolution' => $stored['resolution'],
                'status' => $status,
                'visibility' => $data['visibility'] ?? 'public',
                'allow_comments' => (bool) ($data['allow_comments'] ?? true),
                'published_at' => $status === 'published' ? now() : null,
            ]);

            if (! empty($data['tags'])) {
                $video->tags()->sync(self::tagIds($data['tags']));
            }

            if ($video->category_id) {
                $video->categories()->syncWithoutDetaching([$video->category_id]);
            }

            $channel->increment('videos_count');

            return $video;
        });

        if ($video->status === 'published') {
            NotificationService::notifySubscribers($video->channel, $video);
        }

        return ['video' => $video, 'ffmpeg_available' => (bool) ($stored['has_ffmpeg'] ?? false)];
    }

    public static function tagIds(string $tags): array
    {
        return collect(explode(',', $tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->take(15)
            ->map(fn ($tag) => \App\Models\Tag::findOrCreateByName($tag)->id)
            ->values()
            ->all();
    }

    public static function feedQuery(string $type = 'video')
    {
        return Video::query()
            ->with(['channel', 'category', 'user.profile'])
            ->where('type', $type)
            ->where('status', 'published')
            ->where('visibility', 'public');
    }

    public static function recordView(Video $video, ?User $user, ?string $sessionKey, int $position = 0, bool $completed = false): void
    {
        $alreadyViewed = VideoView::query()
            ->where('video_id', $video->id)
            ->where(function ($query) use ($user, $sessionKey) {
                if ($user) {
                    $query->where('user_id', $user->id);
                } else {
                    $query->where('session_key', $sessionKey);
                }
            })
            ->where('viewed_at', '>=', now()->subHours(6))
            ->exists();

        VideoView::create([
            'video_id' => $video->id,
            'user_id' => $user?->id,
            'ip_address' => request()->ip(),
            'session_key' => $sessionKey,
            'position' => max(0, $position),
            'completed' => $completed,
            'viewed_at' => now(),
        ]);

        if (! $alreadyViewed) {
            $video->increment('views_count');
            $video->channel()->increment('total_views');
        }
    }

    public static function toggleReaction(Video $video, User $user, string $type): array
    {
        return DB::transaction(function () use ($video, $user, $type) {
            $existing = VideoReaction::where('video_id', $video->id)->where('user_id', $user->id)->first();
            $column = $type === 'like' ? 'likes_count' : 'dislikes_count';

            if ($existing && $existing->type === $type) {
                $existing->delete();
                Counters::decrement($video, $column);
                $state = null;
            } elseif ($existing) {
                $previous = $existing->type;
                $existing->update(['type' => $type]);
                Counters::decrement($video, $previous === 'like' ? 'likes_count' : 'dislikes_count');
                Counters::increment($video, $column);
                $state = $type;
            } else {
                VideoReaction::create(['video_id' => $video->id, 'user_id' => $user->id, 'type' => $type]);
                Counters::increment($video, $column);
                $state = $type;
            }

            $video->refresh();

            return [
                'state' => $state,
                'likes' => $video->likes_count,
                'dislikes' => $video->dislikes_count,
            ];
        });
    }

    public static function related(Video $video, int $limit = 12)
    {
        return static::feedQuery($video->type)
            ->where('id', '!=', $video->id)
            ->when($video->category_id, fn ($q) => $q->orderByRaw('category_id = ? desc', [$video->category_id]))
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    public static function attachUserState($videos, ?User $user): void
    {
        if (! $user) {
            return;
        }

        $videos = $videos instanceof \Illuminate\Support\Collection ? $videos : collect([$videos]);
        if ($videos->isEmpty()) {
            return;
        }

        $reactions = VideoReaction::query()
            ->where('user_id', $user->id)
            ->whereIn('video_id', $videos->pluck('id'))
            ->pluck('type', 'video_id');

        $subscribed = \App\Models\Subscription::query()
            ->where('subscriber_id', $user->id)
            ->pluck('channel_id')
            ->flip();

        foreach ($videos as $video) {
            $video->my_reaction = $reactions[$video->id] ?? null;
            if ($video->channel) {
                $video->channel->is_subscribed = isset($subscribed[$video->channel_id]);
            }
        }
    }
}
