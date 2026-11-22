<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Playlist extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'title', 'description', 'visibility', 'videos_count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PlaylistVideo::class)->orderBy('position');
    }

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'playlist_videos')->withPivot('position')->withTimestamps();
    }
}
