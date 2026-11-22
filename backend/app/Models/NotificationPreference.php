<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'new_subscriber', 'new_upload', 'comment_reply', 'channel_activity', 'admin_announcement', 'history_paused'];

    protected function casts(): array
    {
        return [
            'new_subscriber' => 'boolean', 'new_upload' => 'boolean', 'comment_reply' => 'boolean',
            'channel_activity' => 'boolean', 'admin_announcement' => 'boolean', 'history_paused' => 'boolean',
        ];
    }
}
