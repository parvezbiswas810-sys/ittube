<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = ['user_id', 'display_name', 'avatar', 'bio', 'country', 'gender', 'birth_date', 'privacy'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'privacy' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? \App\Support\Media::url($this->avatar) : null;
    }
}
