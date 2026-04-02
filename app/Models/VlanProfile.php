<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VlanProfile extends Model
{
    protected $fillable = [
        'profile_name', 'vlan_id', 'vlan_name', 'vlan_type', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
