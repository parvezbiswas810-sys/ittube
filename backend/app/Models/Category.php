<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'icon', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? \App\Support\Media::url($this->icon) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
