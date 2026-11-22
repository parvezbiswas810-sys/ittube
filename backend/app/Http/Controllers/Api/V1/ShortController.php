<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\Video;
use App\Services\VideoService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ShortController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('sanctum');

        $shorts = Video::query()
            ->with(['channel', 'category', 'user.profile'])
            ->where('type', 'short')
            ->when($user, fn ($q) => $q->visibleTo($user), fn ($q) => $q->published())
            ->orderByDesc('published_at')
            ->paginate((int) $request->input('per_page', 10));

        VideoService::attachUserState(collect($shorts->items()), $user);

        return ApiResponse::success(VideoResource::collection($shorts->getCollection()), 'Shorts loaded', 200, [
            'current_page' => $shorts->currentPage(),
            'per_page' => $shorts->perPage(),
            'total' => $shorts->total(),
            'last_page' => $shorts->lastPage(),
            'has_more' => $shorts->hasMorePages(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $short = Video::with(['channel', 'category', 'user.profile'])->where('type', 'short')->find($id);

        if (! $short) {
            return ApiResponse::error('Short not found.', [], 404);
        }

        VideoService::attachUserState(collect([$short]), $request->user('sanctum'));

        return ApiResponse::success(new VideoResource($short));
    }
}
