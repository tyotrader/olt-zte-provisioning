<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GemportTemplate extends Model
{
    protected $fillable = [
        'template_name', 'gemport_id', 'tcont_profile', 'traffic_class', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
