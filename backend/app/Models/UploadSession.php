<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadSession extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'original_name',
        'mime_type',
        'total_size',
        'total_chunks',
        'received_chunks',
        'status',
        'video_id',
        'payload',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'completed_at' => 'datetime',
        'total_size' => 'integer',
        'total_chunks' => 'integer',
        'received_chunks' => 'integer',
    ];

    public function progress(): int
    {
        if ($this->total_chunks === 0) {
            return 0;
        }

        return (int) floor($this->received_chunks / $this->total_chunks * 100);
    }
}
