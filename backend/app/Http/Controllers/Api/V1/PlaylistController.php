<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Models\Video;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Counters;

class PlaylistController extends Controller
{
    public function index(Request $request)
    {
        $playlists = Playlist::with(['videos.channel', 'videos.category'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(PlaylistResource::collection($playlists));
    }

    public function show(Request $request, int $id)
    {
        $playlist = Playlist::with(['videos.channel', 'videos.category'])->find($id);
        if (! $playlist) {
            return ApiResponse::error('Playlist not found.', [], 404);
        }

        if ($playlist->visibility === 'private' && $playlist->user_id !== $request->user('sanctum')?->id) {
            return ApiResponse::error('Playlist not found.', [], 404);
        }

        return ApiResponse::success(new PlaylistResource($playlist));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['nullable', 'in:public,private'],
        ]);

        $playlist = Playlist::create($data + ['user_id' => $request->user()->id, 'visibility' => $data['visibility'] ?? 'public']);

        return ApiResponse::success(new PlaylistResource($playlist), 'Playlist created successfully.', 201);
    }

    public function update(Request $request, int $id)
    {
        $playlist = $this->owned($request, $id);
        if (! $playlist) {
            return ApiResponse::error('Playlist not found.', [], 404);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['nullable', 'in:public,private'],
        ]);

        $playlist->update($data);

        return ApiResponse::success(new PlaylistResource($playlist->fresh()), 'Playlist updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $playlist = $this->owned($request, $id);
        if (! $playlist) {
            return ApiResponse::error('Playlist not found.', [], 404);
        }

        $playlist->delete();

        return ApiResponse::success(null, 'Playlist deleted successfully.');
    }

    public function addVideo(Request $request, int $id)
    {
        $playlist = $this->owned($request, $id);
        if (! $playlist) {
            return ApiResponse::error('Playlist not found.', [], 404);
        }

        $data = $request->validate(['video_id' => ['required', 'integer', 'exists:videos,id']]);

        DB::transaction(function () use ($playlist, $data) {
            $exists = $playlist->items()->where('video_id', $data['video_id'])->exists();
            if (! $exists) {
                $playlist->items()->create([
                    'video_id' => $data['video_id'],
                    'position' => (int) $playlist->items()->max('position') + 1,
                ]);
                $playlist->increment('videos_count');
            }
        });

        return ApiResponse::success(new PlaylistResource($playlist->fresh(['videos.channel'])), 'Video added to playlist.');
    }

    public function removeVideo(Request $request, int $id, int $videoId)
    {
        $playlist = $this->owned($request, $id);
        if (! $playlist) {
            return ApiResponse::error('Playlist not found.', [], 404);
        }

        DB::transaction(function () use ($playlist, $videoId) {
            $deleted = $playlist->items()->where('video_id', $videoId)->delete();
            if ($deleted) {
                Counters::decrement($playlist, 'videos_count');
            }
        });

        return ApiResponse::success(null, 'Video removed from playlist.');
    }

    public function reorder(Request $request, int $id)
    {
        $playlist = $this->owned($request, $id);
        if (! $playlist) {
            return ApiResponse::error('Playlist not found.', [], 404);
        }

        $data = $request->validate([
            'video_ids' => ['required', 'array'],
            'video_ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($playlist, $data) {
            foreach ($data['video_ids'] as $position => $videoId) {
                $playlist->items()->where('video_id', $videoId)->update(['position' => $position]);
            }
        });

        return ApiResponse::success(new PlaylistResource($playlist->fresh(['videos.channel'])), 'Playlist reordered.');
    }

    protected function owned(Request $request, int $id): ?Playlist
    {
        return Playlist::where('id', $id)->where('user_id', $request->user()->id)->first();
    }
}
