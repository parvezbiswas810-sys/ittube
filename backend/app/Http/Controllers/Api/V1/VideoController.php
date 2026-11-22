<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\Category;
use App\Models\Report;
use App\Models\Tag;
use App\Models\Video;
use App\Models\WatchHistory;
use App\Services\NotificationService;
use App\Services\SettingService;
use App\Services\UploadService;
use App\Services\VideoService;
use App\Support\ApiResponse;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\Counters;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'channel' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:recent,popular,trending,oldest'],
            'type' => ['nullable', 'in:video,short'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user('sanctum');
        $query = Video::query()
            ->with(['channel.category', 'category', 'user.profile', 'tags'])
            ->when($user, fn ($q) => $q->visibleTo($user), fn ($q) => $q->published())
            ->when($user, fn ($q) => $q->where(fn ($inner) => $inner->where('status', 'published')->orWhere('user_id', $user->id)));

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term));
        }

        if ($request->filled('category')) {
            $category = $request->string('category');
            $query->where(function ($q) use ($category) {
                $q->whereHas('categories', fn ($inner) => $inner->where('slug', $category)->orWhere('categories.id', $category))
                    ->orWhereHas('category', fn ($inner) => $inner->where('slug', $category)->orWhere('categories.id', $category));
            });
        }

        if ($request->filled('channel')) {
            $handle = ltrim($request->string('channel'), '@');
            $query->whereHas('channel', fn ($q) => $q->where('handle', $handle));
        }

        match ($request->input('sort', 'recent')) {
            'popular' => $query->orderByDesc('views_count'),
            'trending' => $query->orderByDesc('views_count')->orderByDesc('published_at'),
            'oldest' => $query->orderBy('published_at'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $videos = $query->paginate((int) $request->input('per_page', 15));
        VideoService::attachUserState(collect($videos->items()), $user);

        return ApiResponse::success(VideoResource::collection($videos->getCollection()), 'Videos loaded', 200, [
            'current_page' => $videos->currentPage(),
            'per_page' => $videos->perPage(),
            'total' => $videos->total(),
            'last_page' => $videos->lastPage(),
            'has_more' => $videos->hasMorePages(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user('sanctum');
        $video = Video::with(['channel.category', 'category', 'user.profile', 'tags'])
            ->where('id', $id)->orWhere('slug', $id)->first();

        if (! $video) {
            return ApiResponse::error('Video not found.', [], 404);
        }

        if (! $video->isVisibleTo($user) && $video->status !== 'published') {
            return ApiResponse::error('Video not found.', [], 404);
        }

        VideoService::attachUserState(collect([$video]), $user);

        return ApiResponse::success(new VideoResource($video));
    }

    public function mine(Request $request)
    {
        $videos = Video::with(['channel', 'category'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 15));

        return ApiResponse::success(VideoResource::collection($videos->getCollection()), 'Your uploads', 200, [
            'current_page' => $videos->currentPage(),
            'per_page' => $videos->perPage(),
            'total' => $videos->total(),
            'last_page' => $videos->lastPage(),
            'has_more' => $videos->hasMorePages(),
        ]);
    }

    public function store(Request $request, UploadService $uploadService)
    {
        if (! SettingService::bool('uploads_enabled', true)) {
            return ApiResponse::error('Uploads are currently disabled.', [], 403);
        }

        $user = $request->user();
        $formats = explode(',', (string) SettingService::get('allowed_video_formats', 'mp4,mov,mkv,webm'));
        $maxKb = (int) SettingService::get('max_upload_size_mb', 512) * 1024;

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'video' => ['required', 'file', 'mimes:'.implode(',', $formats), 'max:'.$maxKb],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['nullable', 'in:video,short'],
            'visibility' => ['nullable', 'in:public,unlisted,private'],
            'tags' => ['nullable', 'string', 'max:400'],
            'allow_comments' => ['nullable', 'boolean'],
            'allow_download' => ['nullable', 'boolean'],
        ]);

        $result = VideoService::createFromUpload(
            $user,
            $request->file('video'),
            $data,
            $request->file('thumbnail'),
            $uploadService
        );

        $video = $result['video']->load(['channel', 'category', 'tags']);

        return ApiResponse::success([
            'video' => new VideoResource($video),
            'ffmpeg_available' => $result['ffmpeg_available'],
        ], $video->status === 'published' ? 'Video uploaded successfully.' : 'Video uploaded and is pending review.', 201);
    }

    public function update(Request $request, int $id)
    {
        $video = Video::find($id);

        if (! $video) {
            return ApiResponse::error('Video not found.', [], 404);
        }

        if ($video->user_id !== $request->user()->id) {
            return ApiResponse::error('You are not allowed to edit this video.', [], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'min:3', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'visibility' => ['nullable', 'in:public,unlisted,private'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tags' => ['nullable', 'string', 'max:400'],
            'allow_comments' => ['nullable', 'boolean'],
            'allow_download' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = Media::storeImage($request->file('thumbnail'), 'thumbnails/'.date('Y/m'), $video->thumbnail);
        }

        if (array_key_exists('allow_download', $data)) {
            $data['allow_download'] = $request->boolean('allow_download');
        }

        if (array_key_exists('allow_comments', $data)) {
            $data['allow_comments'] = $request->boolean('allow_comments');
        }

        $video->fill($data)->save();

        if (array_key_exists('tags', $data)) {
            $video->tags()->sync(VideoService::tagIds((string) $data['tags']));
        }

        return ApiResponse::success(new VideoResource($video->fresh(['channel', 'category', 'tags'])), 'Video updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $video = Video::find($id);

        if (! $video) {
            return ApiResponse::error('Video not found.', [], 404);
        }

        if ($video->user_id !== $request->user()->id) {
            return ApiResponse::error('You are not allowed to delete this video.', [], 403);
        }

        $video->delete();
        if ($video->channel) {
            Counters::decrement($video->channel, 'videos_count');
        }

        return ApiResponse::success(null, 'Video deleted successfully.');
    }

    public function like(Request $request, int $id)
    {
        return $this->react($request, $id, 'like');
    }

    public function dislike(Request $request, int $id)
    {
        return $this->react($request, $id, 'dislike');
    }

    protected function react(Request $request, int $id, string $type)
    {
        $video = Video::published()->find($id);

        if (! $video) {
            return ApiResponse::error('Video not found.', [], 404);
        }

        $result = VideoService::toggleReaction($video, $request->user(), $type);

        return ApiResponse::success($result, $result['state'] === null ? 'Reaction removed.' : 'Reaction saved.');
    }

    public function view(Request $request, int $id)
    {
        $data = $request->validate([
            'position' => ['nullable', 'integer', 'min:0'],
            'completed' => ['nullable', 'boolean'],
            'session_key' => ['nullable', 'string', 'max:64'],
        ]);

        $video = Video::find($id);
        if (! $video) {
            return ApiResponse::error('Video not found.', [], 404);
        }

        $user = $request->user('sanctum');
        VideoService::recordView($video, $user, $data['session_key'] ?? null, (int) ($data['position'] ?? 0), (bool) ($data['completed'] ?? false));

        if ($user && ! $user->notificationPreference?->history_paused) {
            WatchHistory::updateOrCreate(
                ['user_id' => $user->id, 'video_id' => $video->id],
                [
                    'position' => (int) ($data['position'] ?? 0),
                    'duration' => $video->duration,
                    'completed' => (bool) ($data['completed'] ?? false),
                    'last_watched_at' => now(),
                ]
            );
        }

        return ApiResponse::success(['views_count' => $video->fresh()->views_count], 'View recorded.');
    }

    public function related(int $id)
    {
        $video = Video::find($id);
        if (! $video) {
            return ApiResponse::error('Video not found.', [], 404);
        }

        return ApiResponse::success(VideoResource::collection(VideoService::related($video, 12)));
    }

    public function report(Request $request, int $id)
    {
        $video = Video::find($id);
        if (! $video) {
            return ApiResponse::error('Video not found.', [], 404);
        }

        $data = $request->validate([
            'reason' => ['required', 'in:spam,harassment,violence,sexual_content,copyright,misinformation,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => Video::class,
            'reportable_id' => $video->id,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ]);

        return ApiResponse::success(null, 'Report submitted. Our moderation team will review it.', 201);
    }

}
