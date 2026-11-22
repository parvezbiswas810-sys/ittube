<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    use SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role_id', 'status', 'last_login_at', 'last_login_ip'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['last_login_at' => 'datetime', 'password' => 'hashed'];
    }

    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->role?->is_super;
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return (bool) $this->role?->permissions()->where('slug', $slug)->exists();
    }
}
