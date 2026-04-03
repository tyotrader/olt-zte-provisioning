<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Olt;
use App\Services\SNMPService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PollOltStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $oltId;
    public $timeout;

    /**
     * Create a new job instance.
     */
    public function __construct(int $oltId, int $timeout = 30)
    {
        $this->oltId = $oltId;
        $this->timeout = $timeout;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $olt = Olt::find($this->oltId);
        
        if (!$olt || !$olt->is_active) {
            return;
        }

        try {
            $snmp = new SNMPService($olt);
            
            // Get OLT status
            $status = $snmp->getOltStatus();
            $uplink = $snmp->getUplinkTraffic(1);
            
            // Cache the results for fast dashboard access
            $cacheKey = "olt_status:{$this->oltId}";
            Cache::put($cacheKey, [
                'olt_id' => $olt->id,
                'olt_name' => $olt->olt_name,
                'cpu_usage' => $status['cpu_usage'] ?? 0,
                'temperature' => $status['temperature'] ?? 0,
                'memory_usage' => $status['memory_usage'] ?? 0,
                'rx_bytes' => $uplink['rx_bytes'] ?? 0,
                'tx_bytes' => $uplink['tx_bytes'] ?? 0,
                'polled_at' => now()->toDateTimeString(),
            ], $this->timeout);
            
            // Update OLT last_poll timestamp
            $olt->update([
                'last_poll' => now(),
                'status' => 'online'
            ]);
            
            Log::info("OLT {$olt->olt_name} polled successfully", [
                'cpu' => $status['cpu_usage'],
                'temp' => $status['temperature']
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to poll OLT {$olt->olt_name}: " . $e->getMessage());
            
            // Mark OLT as offline after multiple failures
            $failures = Cache::increment("olt_failures:{$this->oltId}", 1);
            if ($failures >= 3) {
                $olt->update(['status' => 'offline']);
            }
            
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("OLT polling job failed permanently: " . $exception->getMessage());
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags()
    {
        return ['olt', 'snmp', 'polling', "olt:{$this->oltId}"];
    }
}
