<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CopyrightMatch extends Model
{
    protected $fillable = ['reference_id', 'video_id', 'match_type', 'confidence', 'source'];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
