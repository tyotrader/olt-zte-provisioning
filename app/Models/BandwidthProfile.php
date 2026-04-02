<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandwidthProfile extends Model
{
    protected $fillable = [
        'profile_name', 'profile_type', 'fixed_bw', 'assure_bw', 'max_bw', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
