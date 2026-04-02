<?php

namespace App\Controllers;

use App\Models\Olt;
use App\Models\PonPort;
use App\Services\SNMPService;
use Illuminate\Http\Request;

class PonController extends Controller
{
    public function index()
    {
        $ponPorts = PonPort::with('olt')
            ->orderBy('olt_id')
            ->orderBy('slot')
            ->orderBy('port')
            ->paginate(50);

        return view('pon.index', compact('ponPorts'));
    }

    public function show($id)
    {
        $ponPort = PonPort::with(['olt', 'onus'])->findOrFail($id);
        
        return view('pon.show', compact('ponPort'));
    }

    public function getOnus($id)
    {
        $ponPort = PonPort::findOrFail($id);
        $onus = $ponPort->onus()
            ->orderBy('onu_id')
            ->paginate(50);

        return response()->json([
            'pon_port' => $ponPort,
            'onus' => $onus
        ]);
    }

    public function getRealtimeStats($id)
    {
        $ponPort = PonPort::with('olt')->findOrFail($id);
        
        try {
            $snmp = new SNMPService($ponPort->olt);
            
            // Get ONU optical info for this PON port
            $onusStats = [];
            foreach ($ponPort->onus as $onu) {
                $onuIndex = $this->calculateOnuIndex($onu->slot, $onu->pon_port, $onu->onu_id);
                $onusStats[] = [
                    'onu_id' => $onu->onu_id,
                    'customer_name' => $onu->customer_name,
                    'rx_power' => $snmp->getOnuRxPower($onuIndex),
                    'tx_power' => $snmp->getOnuTxPower($onuIndex),
                    'status' => $snmp->getOnuStatus($onuIndex)
                ];
            }

            // Calculate average RX power
            $rxPowers = array_column($onusStats, 'rx_power');
            $avgRxPower = count($rxPowers) > 0 
                ? round(array_sum($rxPowers) / count($rxPowers), 2) 
                : null;

            return response()->json([
                'success' => true,
                'pon_port' => [
                    'id' => $ponPort->id,
                    'name' => "{$ponPort->slot}/{$ponPort->port}",
                    'total_onu' => $ponPort->onus->count(),
                    'online' => $ponPort->online_onu,
                    'offline' => $ponPort->offline_onu,
                    'avg_rx_power' => $avgRxPower
                ],
                'onus' => $onusStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByOlt($oltId)
    {
        $olt = Olt::findOrFail($oltId);
        $ponPorts = PonPort::where('olt_id', $oltId)
            ->withCount(['onus as total_onu', 'onus as online_onu' => function($q) {
                $q->online();
            }, 'onus as offline_onu' => function($q) {
                $q->offline();
            }])
            ->orderBy('slot')
            ->orderBy('port')
            ->get();

        return response()->json($ponPorts);
    }

    private function calculateOnuIndex($slot, $port, $onuId)
    {
        return ($slot * 10000000) + ($port * 100000) + $onuId;
    }
}
