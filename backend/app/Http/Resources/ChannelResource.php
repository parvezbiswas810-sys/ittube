<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'avatar_url' => $this->avatar_url,
            'banner_url' => $this->banner_url,
            'description' => $this->description,
            'subscribers_count' => (int) $this->subscribers_count,
            'videos_count' => (int) $this->videos_count,
            'total_views' => (int) $this->total_views,
            'is_verified' => (bool) $this->is_verified,
            'is_featured' => (bool) $this->is_featured,
            'status' => $this->status,
            'is_subscribed' => (bool) ($this->is_subscribed ?? false),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'links' => $this->whenLoaded('links', fn () => $this->links->map(fn ($link) => [
                'platform' => $link->platform,
                'url' => $link->url,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
