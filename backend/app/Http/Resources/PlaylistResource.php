<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'videos_count' => (int) $this->videos_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'videos' => VideoResource::collection($this->whenLoaded('videos')),
        ];
    }
}
