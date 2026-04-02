<?php

namespace App\Services;

use App\Models\Olt;
use App\Models\OnuDetection;

class DetectionService
{
    private $telnetService;
    private $olt;

    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
        $this->telnetService = new TelnetService($olt);
    }

    public function scanUnregisteredOnus()
    {
        try {
            if (!$this->telnetService->connect()) {
                throw new \Exception('Failed to connect to OLT');
            }
            
            $output = $this->telnetService->execute('show gpon onu uncfg');
            $onus = $this->parseUnregisteredOnus($output);
            
            $results = [];
            foreach ($onus as $onu) {
                $detection = OnuDetection::updateOrCreate(
                    [
                        'olt_id' => $this->olt->id,
                        'slot' => $onu['slot'],
                        'pon_port' => $onu['port'],
                        'onu_sn' => $onu['sn']
                    ],
                    [
                        'onu_type' => $onu['type'] ?? 'Unknown',
                        'discovery_time' => now(),
                        'status' => 'detected',
                        'is_ignored' => false
                    ]
                );
                
                $results[] = $detection;
            }
            
            return [
                'success' => true,
                'count' => count($results),
                'onus' => $results
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function scanAllOlts()
    {
        $olts = Olt::active()->get();
        $allResults = [];
        
        foreach ($olts as $olt) {
            $service = new self($olt);
            $result = $service->scanUnregisteredOnus();
            $allResults[$olt->id] = $result;
        }
        
        return $allResults;
    }

    public function ignoreOnu($detectionId)
    {
        $detection = OnuDetection::findOrFail($detectionId);
        $detection->update(['is_ignored' => true, 'status' => 'ignored']);
        return $detection;
    }

    public function unignoreOnu($detectionId)
    {
        $detection = OnuDetection::findOrFail($detectionId);
        $detection->update(['is_ignored' => false, 'status' => 'detected']);
        return $detection;
    }

    public function deleteDetection($detectionId)
    {
        $detection = OnuDetection::findOrFail($detectionId);
        return $detection->delete();
    }

    private function parseUnregisteredOnus($output)
    {
        $onus = [];
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            // Parse ZTE format: gpon-onu_1/1:1  ZNTSXXXXXXXX  F601
            if (preg_match('/gpon-onu_(\d+)\/(\d+):(\d+)\s+(\S+)\s*(\S*)/', $line, $matches)) {
                $onus[] = [
                    'slot' => (int) $matches[1],
                    'port' => (int) $matches[2],
                    'onu_id' => (int) $matches[3],
                    'sn' => $matches[4],
                    'type' => !empty($matches[5]) ? $matches[5] : 'Unknown'
                ];
            }
            
            // Alternative format
            if (preg_match('/(\d+)\s+(\d+)\s+(\d+)\s+(\S+)\s+(\S+)/', $line, $matches)) {
                $onus[] = [
                    'slot' => (int) $matches[1],
                    'port' => (int) $matches[2],
                    'onu_id' => (int) $matches[3],
                    'sn' => $matches[4],
                    'type' => $matches[5]
                ];
            }
        }
        
        return $onus;
    }

    public function getPendingDetections()
    {
        return OnuDetection::with('olt')
            ->where('status', 'detected')
            ->where('is_ignored', false)
            ->orderBy('discovery_time', 'desc')
            ->get();
    }

    public function getStats()
    {
        return [
            'total_detected' => OnuDetection::count(),
            'pending' => OnuDetection::pending()->count(),
            'ignored' => OnuDetection::ignored()->count(),
            'registered' => OnuDetection::where('status', 'registered')->count(),
            'today_detected' => OnuDetection::whereDate('discovery_time', today())->count()
        ];
    }
}
