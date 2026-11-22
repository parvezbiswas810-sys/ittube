<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\HomeSection;
use App\Models\Video;
use App\Services\VideoService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('sanctum');
        $sections = HomeSection::where('is_active', true)->orderBy('sort_order')->get();
        $payload = [];

        foreach ($sections as $section) {
            $query = Video::query()
                ->with(['channel', 'category', 'user.profile'])
                ->where('status', 'published')
                ->where('visibility', 'public');

            $videos = match ($section->type) {
                'featured' => $query->where('is_featured', true)->orderByDesc('published_at')->limit($section->item_limit)->get(),
                'trending' => $query->orderByDesc('views_count')->orderByDesc('published_at')->limit($section->item_limit)->get(),
                'recent' => $query->orderByDesc('published_at')->limit($section->item_limit)->get(),
                'popular' => $query->orderByDesc('likes_count')->limit($section->item_limit)->get(),
                'category' => $query->where('category_id', $section->category_id)->orderByDesc('published_at')->limit($section->item_limit)->get(),
                'shorts' => $query->where('type', 'short')->orderByDesc('published_at')->limit($section->item_limit)->get(),
                default => $section->videos()
                    ->with(['channel', 'category', 'user.profile'])
                    ->where('status', 'published')
                    ->orderBy('home_section_videos.position')
                    ->limit($section->item_limit)
                    ->get(),
            };

            VideoService::attachUserState($videos, $user);

            $payload[] = [
                'id' => $section->id,
                'title' => $section->title,
                'type' => $section->type,
                'videos' => VideoResource::collection($videos),
            ];
        }

        return ApiResponse::success(['sections' => $payload]);
    }
}
