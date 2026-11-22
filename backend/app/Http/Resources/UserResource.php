<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->when($request->user()?->id === $this->id, $this->email),
            'display_name' => $this->displayName(),
            'avatar_url' => $this->avatarUrl(),
            'role' => $this->role,
            'status' => $this->status,
            'bio' => $this->profile?->bio,
            'country' => $this->profile?->country,
            'email_verified' => (bool) $this->email_verified_at,
            'channel' => new ChannelResource($this->whenLoaded('channel')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
