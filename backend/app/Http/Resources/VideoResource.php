<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'thumbnail_url' => $this->thumbnail_url,
            'playback_url' => $this->playback_url,
            'duration' => (int) $this->duration,
            'duration_text' => $this->formatDuration($this->duration),
            'file_size' => (int) $this->file_size,
            'resolution' => $this->resolution,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'views_count' => (int) $this->views_count,
            'likes_count' => (int) $this->likes_count,
            'dislikes_count' => (int) $this->dislikes_count,
            'comments_count' => (int) $this->comments_count,
            'is_featured' => (bool) $this->is_featured,
            'allow_comments' => (bool) $this->allow_comments,
            'allow_download' => (bool) $this->allow_download,
            'my_reaction' => $this->my_reaction ?? null,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'channel' => new ChannelResource($this->whenLoaded('channel')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
        ];
    }

    protected function formatDuration(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }
}
