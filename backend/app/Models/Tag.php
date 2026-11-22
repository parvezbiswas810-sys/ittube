<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);
        $slug = Str::slug($name);

        return static::firstOrCreate(['slug' => $slug], ['name' => $name]);
    }

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'video_tags');
    }
}
