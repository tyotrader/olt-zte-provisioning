<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProvisionLog extends Model
{
    protected $fillable = [
        'olt_id', 'onu_id', 'action', 'command', 'response',
        'status', 'error_message', 'user_id', 'executed_at'
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function onu()
    {
        return $this->belongsTo(Onu::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
