<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TcontProfile extends Model
{
    protected $fillable = [
        'profile_name', 'tcont_id', 'bandwidth_profile', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
