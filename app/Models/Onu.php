<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Onu extends Model
{
    protected $fillable = [
        'olt_id', 'pon_port_id', 'odp_id', 'onu_id', 'onu_sn', 'onu_type',
        'customer_name', 'customer_id', 'address', 'phone', 'slot', 'pon_port',
        'tcont_profile', 'gemport_template', 'vlan_profile', 'service_port_template',
        'wan_mode', 'pppoe_username', 'pppoe_password', 'static_ip', 'static_gateway', 'static_subnet',
        'wifi_ssid', 'wifi_password', 'wifi_enabled',
        'rx_power', 'tx_power', 'distance', 'temperature', 'firmware_version', 'uptime',
        'status', 'last_seen', 'registered_at', 'latitude', 'longitude',
        'notes', 'is_active'
    ];

    protected $hidden = [
        'pppoe_password', 'wifi_password'
    ];

    protected $casts = [
        'rx_power' => 'decimal:2',
        'tx_power' => 'decimal:2',
        'distance' => 'decimal:2',
        'temperature' => 'decimal:2',
        'wifi_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_seen' => 'datetime',
        'registered_at' => 'datetime',
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function ponPort()
    {
        return $this->belongsTo(PonPort::class);
    }

    public function odp()
    {
        return $this->belongsTo(Odp::class);
    }

    public function traffic()
    {
        return $this->hasMany(OnuTraffic::class);
    }

    public function provisionLogs()
    {
        return $this->hasMany(ProvisionLog::class);
    }

    public function getFullOnuAddressAttribute()
    {
        return "gpon-onu_{$this->slot}/{$this->pon_port}:{$this->onu_id}";
    }

    public function getSignalStatusAttribute()
    {
        if ($this->rx_power === null) return 'unknown';
        if ($this->rx_power >= -25) return 'good';
        if ($this->rx_power >= -27) return 'warning';
        return 'critical';
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
