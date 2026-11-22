<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelLink extends Model
{
    protected $fillable = ['channel_id', 'platform', 'url'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
