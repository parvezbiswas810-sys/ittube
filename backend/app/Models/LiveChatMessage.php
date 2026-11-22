<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveChatMessage extends Model
{
    protected $fillable = ['live_stream_id', 'user_id', 'author_name', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
