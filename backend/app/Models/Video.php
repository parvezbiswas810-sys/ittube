<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Video extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'processing', 'pending', 'published', 'rejected', 'restricted', 'suspended', 'failed'];
    public const VISIBILITIES = ['public', 'unlisted', 'private'];

    protected $fillable = [
        'user_id', 'channel_id', 'category_id', 'title', 'slug', 'description', 'type',
        'thumbnail', 'video_path', 'mime_type', 'duration', 'file_size', 'resolution',
        'status', 'visibility', 'views_count', 'likes_count', 'dislikes_count', 'comments_count',
        'is_featured', 'allow_comments', 'rejection_reason', 'processing_error', 'published_at',
    ];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'allow_comments' => 'boolean', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Video $video) {
            if (blank($video->slug)) {
                $video->slug = static::uniqueSlug($video->title);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'video';
        $slug = $base;
        $i = 1;
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'video_categories');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'video_tags');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions()
    {
        return $this->hasMany(VideoReaction::class);
    }

    public function views()
    {
        return $this->hasMany(VideoView::class);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? \App\Support\Media::url($this->thumbnail) : null;
    }

    public function getPlaybackUrlAttribute(): ?string
    {
        return $this->video_path ? route('media.video', $this) : null;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('visibility', 'public');
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public');
            if ($user) {
                $q->orWhere('user_id', $user->id);
            }
        });
    }

    public function isVisibleTo(?User $user): bool
    {
        if ($this->visibility === 'public' && $this->status === 'published') {
            return true;
        }

        return $user && ($this->user_id === $user->id);
    }
}
