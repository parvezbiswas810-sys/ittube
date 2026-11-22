<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\NotificationPreference;
use App\Models\WatchHistory;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $history = WatchHistory::with(['video.channel', 'video.category'])
            ->where('user_id', $request->user()->id)
            ->whereHas('video')
            ->orderByDesc('last_watched_at')
            ->paginate((int) $request->input('per_page', 20));

        $items = $history->getCollection()->map(fn ($item) => [
            'history_id' => $item->id,
            'position' => $item->position,
            'completed' => $item->completed,
            'last_watched_at' => $item->last_watched_at?->toIso8601String(),
            'video' => new VideoResource($item->video),
        ]);

        return ApiResponse::success($items, 'Watch history loaded', 200, [
            'current_page' => $history->currentPage(),
            'per_page' => $history->perPage(),
            'total' => $history->total(),
            'last_page' => $history->lastPage(),
            'has_more' => $history->hasMorePages(),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $deleted = WatchHistory::where('id', $id)->where('user_id', $request->user()->id)->delete();

        return $deleted
            ? ApiResponse::success(null, 'History item removed.')
            : ApiResponse::error('History item not found.', [], 404);
    }

    public function clear(Request $request)
    {
        WatchHistory::where('user_id', $request->user()->id)->delete();

        return ApiResponse::success(null, 'Watch history cleared.');
    }

    public function togglePause(Request $request)
    {
        $data = $request->validate(['paused' => ['required', 'boolean']]);
        $preferences = NotificationPreference::firstOrCreate(['user_id' => $request->user()->id]);
        $preferences->update(['history_paused' => $data['paused']]);

        return ApiResponse::success(['paused' => $preferences->history_paused], 'History tracking updated.');
    }
}
