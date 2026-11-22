<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiveStream extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'channel_id', 'category_id', 'title', 'description', 'thumbnail',
        'status', 'visibility', 'provider', 'stream_key', 'rtmp_url', 'playback_url',
        'game_title', 'scheduled_for', 'started_at', 'ended_at', 'peak_viewers',
    ];

    protected $hidden = ['stream_key'];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
