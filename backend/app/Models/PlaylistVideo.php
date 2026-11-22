<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaylistVideo extends Model
{
    protected $fillable = ['playlist_id', 'video_id', 'position'];

    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
