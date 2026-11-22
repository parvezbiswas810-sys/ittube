<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchHistory extends Model
{
    protected $fillable = ['user_id', 'video_id', 'position', 'duration', 'completed', 'last_watched_at'];

    protected function casts(): array
    {
        return ['last_watched_at' => 'datetime', 'completed' => 'boolean'];
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
