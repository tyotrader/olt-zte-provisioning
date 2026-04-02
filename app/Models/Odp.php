<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odp extends Model
{
    protected $fillable = [
        'odp_name', 'olt_id', 'pon_port_id', 'location', 'address',
        'latitude', 'longitude', 'total_ports', 'used_ports',
        'description', 'status'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function ponPort()
    {
        return $this->belongsTo(PonPort::class);
    }

    public function onus()
    {
        return $this->hasMany(Onu::class);
    }

    public function getAvailabilityPercentageAttribute()
    {
        if ($this->total_ports == 0) return 0;
        return round((($this->total_ports - $this->used_ports) / $this->total_ports) * 100, 2);
    }
}
