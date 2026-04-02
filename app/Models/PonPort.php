<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PonPort extends Model
{
    protected $fillable = [
        'olt_id', 'slot', 'port', 'pon_type', 'max_onu',
        'current_onu_count', 'online_onu', 'offline_onu',
        'average_rx_power', 'admin_status', 'oper_status', 'utilization'
    ];

    protected $casts = [
        'average_rx_power' => 'decimal:2',
        'utilization' => 'decimal:2',
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function onus()
    {
        return $this->hasMany(Onu::class);
    }

    public function odps()
    {
        return $this->hasMany(Odp::class);
    }

    public function getFullPortNameAttribute()
    {
        return "gpon-onu_{$this->slot}/{$this->port}";
    }

    public function getAvailableOnuId()
    {
        $usedIds = $this->onus()->pluck('onu_id')->toArray();
        for ($i = 1; $i <= $this->max_onu; $i++) {
            if (!in_array($i, $usedIds)) {
                return $i;
            }
        }
        return null;
    }
}
