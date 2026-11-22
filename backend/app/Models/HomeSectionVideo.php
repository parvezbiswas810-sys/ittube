<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSectionVideo extends Model
{
    public $timestamps = false;

    protected $fillable = ['section_id', 'video_id', 'position'];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
