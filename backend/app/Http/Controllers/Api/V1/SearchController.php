<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\VideoResource;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Video;
use App\Services\VideoService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:120'],
            'filter' => ['nullable', 'in:all,videos,channels,categories,shorts'],
            'sort' => ['nullable', 'in:relevance,recent,popular'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $term = trim($data['q']);
        $like = '%'.$term.'%';
        $filter = $data['filter'] ?? 'all';
        $perPage = (int) ($data['per_page'] ?? 15);
        $user = $request->user('sanctum');

        $videos = collect();
        if (in_array($filter, ['all', 'videos', 'shorts'], true)) {
            $videos = Video::query()
                ->with(['channel', 'category', 'user.profile'])
                ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like))
                ->where('type', $filter === 'shorts' ? 'short' : 'video')
                ->when($user, fn ($q) => $q->visibleTo($user), fn ($q) => $q->published())
                ->when($data['sort'] ?? 'relevance', fn ($q, $sort) => match ($sort) {
                    'recent' => $q->orderByDesc('published_at'),
                    'popular' => $q->orderByDesc('views_count'),
                    default => $q->orderByRaw('case when title like ? then 0 else 1 end', [$term.'%'])->orderByDesc('views_count'),
                })
                ->paginate($perPage);
            VideoService::attachUserState(collect($videos->items()), $user);
        }

        $channels = collect();
        if (in_array($filter, ['all', 'channels'], true)) {
            $channels = Channel::query()
                ->with('category')
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('handle', 'like', $like))
                ->where('status', 'active')
                ->orderByDesc('subscribers_count')
                ->limit(10)
                ->get();
        }

        $categories = collect();
        if (in_array($filter, ['all', 'categories'], true)) {
            $categories = Category::active()->where('name', 'like', $like)->limit(10)->get();
        }

        return ApiResponse::success([
            'query' => $term,
            'videos' => VideoResource::collection($videos->getCollection() ?? collect()),
            'channels' => ChannelResource::collection($channels),
            'categories' => CategoryResource::collection($categories),
            'suggestions' => Video::query()->published()->where('title', 'like', $term.'%')->limit(8)->pluck('title'),
        ], 'Search results', 200, $videos instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? [
            'current_page' => $videos->currentPage(),
            'per_page' => $videos->perPage(),
            'total' => $videos->total(),
            'last_page' => $videos->lastPage(),
            'has_more' => $videos->hasMorePages(),
        ] : null);
    }
}
