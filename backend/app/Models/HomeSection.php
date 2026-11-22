<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = ['title', 'type', 'category_id', 'item_limit', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function items()
    {
        return $this->hasMany(HomeSectionVideo::class, 'section_id')->orderBy('position');
    }

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'home_section_videos', 'section_id', 'video_id')->withPivot('position');
    }
}
