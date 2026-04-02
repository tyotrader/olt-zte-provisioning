<?php

namespace App\Controllers;

use App\Models\Olt;
use App\Models\Onu;
use App\Models\PonPort;
use App\Models\OnuDetection;
use App\Services\SNMPService;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->getStats();
        return view('dashboard.index', compact('stats'));
    }

    public function getStats()
    {
        $totalOlts = Olt::count();
        $totalPonPorts = PonPort::count();
        $totalOnus = Onu::count();
        $onlineOnus = Onu::online()->count();
        $offlineOnus = Onu::offline()->count();
        $unregisteredOnus = OnuDetection::pending()->count();

        // Calculate utilization
        $ponUtilization = $totalPonPorts > 0 
            ? round(($totalOnus / ($totalPonPorts * 128)) * 100, 2) 
            : 0;

        return [
            'total_olts' => $totalOlts,
            'total_pon_ports' => $totalPonPorts,
            'total_onus' => $totalOnus,
            'online_onus' => $onlineOnus,
            'offline_onus' => $offlineOnus,
            'unregistered_onus' => $unregisteredOnus,
            'pon_utilization' => $ponUtilization,
            'online_percentage' => $totalOnus > 0 ? round(($onlineOnus / $totalOnus) * 100, 2) : 0
        ];
    }

    public function getRealtimeData()
    {
        $olts = Olt::active()->get();
        $oltData = [];
        
        foreach ($olts as $olt) {
            try {
                $snmp = new SNMPService($olt);
                $status = $snmp->getOltStatus();
                $uplink = $snmp->getUplinkTraffic(1);
                
                $oltData[] = [
                    'olt_id' => $olt->id,
                    'olt_name' => $olt->olt_name,
                    'cpu_usage' => $status['cpu_usage'] ?? 0,
                    'temperature' => $status['temperature'] ?? 0,
                    'memory_usage' => $status['memory_usage'] ?? 0,
                    'rx_traffic' => $this->formatBytes($uplink['rx_bytes'] ?? 0),
                    'tx_traffic' => $this->formatBytes($uplink['tx_bytes'] ?? 0),
                ];
            } catch (\Exception $e) {
                $oltData[] = [
                    'olt_id' => $olt->id,
                    'olt_name' => $olt->olt_name,
                    'error' => 'SNMP unavailable'
                ];
            }
        }

        // Get ONU status distribution
        $onuStatus = [
            'online' => Onu::online()->count(),
            'offline' => Onu::offline()->count(),
            'warning' => Onu::where('rx_power', '<', -25)->where('rx_power', '>=', -27)->count(),
            'critical' => Onu::where('rx_power', '<', -27)->count()
        ];

        // Get traffic trend (last 24 hours)
        $trafficTrend = $this->getTrafficTrend();

        return response()->json([
            'olts' => $oltData,
            'onu_status' => $onuStatus,
            'traffic_trend' => $trafficTrend,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    public function getChartData()
    {
        // ONU Online Chart Data (last 30 days)
        $onuHistory = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $onuHistory[] = [
                'date' => $date,
                'online' => rand(80, 100), // Replace with actual historical data
                'offline' => rand(0, 20)
            ];
        }

        // PON Utilization by OLT
        $ponUtilization = Olt::withCount('onus')
            ->get()
            ->map(function ($olt) {
                $maxOnus = $olt->ponPorts()->count() * 128;
                return [
                    'name' => $olt->olt_name,
                    'utilization' => $maxOnus > 0 ? round(($olt->onus_count / $maxOnus) * 100, 2) : 0
                ];
            });

        return response()->json([
            'onu_history' => $onuHistory,
            'pon_utilization' => $ponUtilization
        ]);
    }

    private function getTrafficTrend()
    {
        // Placeholder for traffic trend data
        // In production, query onu_traffic table
        $data = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('H:00');
            $data[] = [
                'hour' => $hour,
                'rx' => rand(1000000, 5000000),
                'tx' => rand(500000, 3000000)
            ];
        }
        return $data;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
