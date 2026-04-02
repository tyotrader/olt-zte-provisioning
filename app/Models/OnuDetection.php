<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnuDetection extends Model
{
    protected $table = 'onu_detection';
    
    protected $fillable = [
        'olt_id', 'slot', 'pon_port', 'onu_sn', 'onu_password',
        'onu_type', 'loid', 'loid_password', 'firmware_version',
        'hardware_version', 'discovery_time', 'status', 'is_ignored', 'registered_at'
    ];

    protected $casts = [
        'is_ignored' => 'boolean',
        'discovery_time' => 'datetime',
        'registered_at' => 'datetime',
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function onu()
    {
        return $this->hasOne(Onu::class, 'onu_sn', 'onu_sn');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'detected')->where('is_ignored', false);
    }

    public function scopeIgnored($query)
    {
        return $query->where('is_ignored', true);
    }
}
