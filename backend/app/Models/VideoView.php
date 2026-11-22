<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoView extends Model
{
    public $timestamps = false;

    protected $fillable = ['video_id', 'user_id', 'ip_address', 'session_key', 'position', 'completed', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime', 'completed' => 'boolean'];
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
