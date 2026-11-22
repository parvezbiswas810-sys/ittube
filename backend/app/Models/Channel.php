<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Channel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'name', 'handle', 'avatar', 'banner', 'description',
        'subscribers_count', 'videos_count', 'total_views', 'is_verified', 'is_featured', 'status',
    ];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'is_featured' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function links()
    {
        return $this->hasMany(ChannelLink::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? \App\Support\Media::url($this->avatar) : null;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner ? \App\Support\Media::url($this->banner) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
