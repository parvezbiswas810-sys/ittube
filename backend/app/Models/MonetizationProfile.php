<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonetizationProfile extends Model
{
    protected $fillable = ['user_id', 'status', 'ads_enabled', 'payout_method', 'payout_reference', 'admin_note'];

    protected function casts(): array
    {
        return ['ads_enabled' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
