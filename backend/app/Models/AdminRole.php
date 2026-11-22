<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRole extends Model
{
    protected $fillable = ['name', 'slug', 'is_super', 'description'];

    protected function casts(): array
    {
        return ['is_super' => 'boolean'];
    }

    public function permissions()
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_role_permissions', 'role_id', 'permission_id');
    }

    public function admins()
    {
        return $this->hasMany(AdminUser::class, 'role_id');
    }
}
