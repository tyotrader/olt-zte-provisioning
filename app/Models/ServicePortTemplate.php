<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePortTemplate extends Model
{
    protected $fillable = [
        'template_name', 'service_port_id', 'vport', 'user_vlan', 'c_vid',
        'vlan_mode', 'translation_mode', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
