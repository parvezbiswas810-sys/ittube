<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $fillable = ['admin_id', 'action', 'description', 'target_type', 'target_id', 'ip_address', 'user_agent'];

    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }
}
