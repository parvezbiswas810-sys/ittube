<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CopyrightReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reporter_id', 'content_type', 'content_id', 'content_url', 'rights_holder',
        'reason', 'description', 'evidence_path', 'contact_name', 'contact_email',
        'status', 'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by');
    }

    public function target()
    {
        return match ($this->content_type) {
            'video', 'short' => Video::withTrashed()->find($this->content_id),
            'channel' => Channel::find($this->content_id),
            default => null,
        };
    }
}
