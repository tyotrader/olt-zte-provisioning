<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Olt extends Model
{
    protected $fillable = [
        'olt_name', 'ip_address', 'olt_model', 'location', 'description',
        'snmp_community', 'snmp_read_community', 'snmp_write_community',
        'snmp_port', 'snmp_version', 'telnet_username', 'telnet_password',
        'telnet_port', 'timeout', 'latitude', 'longitude',
        'is_active', 'last_poll', 'status'
    ];

    protected $hidden = [
        'telnet_password'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_poll' => 'datetime',
    ];

    public function ponPorts()
    {
        return $this->hasMany(PonPort::class);
    }

    public function onus()
    {
        return $this->hasMany(Onu::class);
    }

    public function odps()
    {
        return $this->hasMany(Odp::class);
    }

    public function onuDetections()
    {
        return $this->hasMany(OnuDetection::class);
    }

    public function provisionLogs()
    {
        return $this->hasMany(ProvisionLog::class);
    }

    public function getDecryptedPasswordAttribute()
    {
        return decrypt($this->telnet_password);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
