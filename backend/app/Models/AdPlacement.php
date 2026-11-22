<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdPlacement extends Model
{
    protected $fillable = ['name', 'position', 'offset_seconds', 'ad_tag_url', 'provider', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
