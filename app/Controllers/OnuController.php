<?php

namespace App\Controllers;

use App\Models\Onu;
use App\Models\Olt;
use App\Models\PonPort;
use App\Services\SNMPService;
use App\Services\ProvisioningService;
use Illuminate\Http\Request;

class OnuController extends Controller
{
    public function index(Request $request)
    {
        $query = Onu::with(['olt', 'ponPort']);

        // Filters
        if ($request->has('olt_id')) {
            $query->where('olt_id', $request->olt_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('onu_sn', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%");
            });
        }

        $onus = $query->orderBy('created_at', 'desc')->paginate(50);
        $olts = Olt::active()->get();

        return view('onu.index', compact('onus', 'olts'));
    }

    public function show($id)
    {
        $onu = Onu::with(['olt', 'ponPort', 'odp'])->findOrFail($id);
        
        return view('onu.show', compact('onu'));
    }

    public function getDetail($id)
    {
        $onu = Onu::with(['olt', 'ponPort'])->findOrFail($id);
        
        try {
            $snmp = new SNMPService($onu->olt);
            $realtime = $snmp->pollOnu($onu);
            $traffic = $snmp->pollOnuTraffic($onu);

            return response()->json([
                'success' => true,
                'onu' => $onu,
                'realtime' => $realtime,
                'traffic' => $traffic
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'onu' => $onu,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function edit($id)
    {
        $onu = Onu::findOrFail($id);
        $olts = Olt::active()->get();
        
        return view('onu.edit', compact('onu', 'olts'));
    }

    public function update(Request $request, $id)
    {
        $onu = Onu::findOrFail($id);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_id' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
            'wifi_ssid' => 'nullable|string|max:255',
            'wifi_password' => 'nullable|string|max:255',
            'wifi_enabled' => 'boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        // Encrypt passwords
        if (!empty($validated['pppoe_password'])) {
            $validated['pppoe_password'] = encrypt($validated['pppoe_password']);
        }
        if (!empty($validated['wifi_password'])) {
            $validated['wifi_password'] = encrypt($validated['wifi_password']);
        }

        $onu->update($validated);

        return redirect()->route('onu.index')
            ->with('success', 'ONU updated successfully');
    }

    public function destroy($id)
    {
        $onu = Onu::findOrFail($id);
        
        // Delete from OLT first
        try {
            $provisioning = new ProvisioningService($onu->olt);
            $result = $provisioning->deleteOnu($onu->slot, $onu->pon_port, $onu->onu_id);
            
            if (!$result['success']) {
                return redirect()->back()
                    ->with('error', 'Failed to delete ONU from OLT: ' . $result['error']);
            }
        } catch (\Exception $e) {
            // Log error but continue with database deletion
        }

        $onu->delete();

        return redirect()->route('onu.index')
            ->with('success', 'ONU deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        
        foreach ($ids as $id) {
            $onu = Onu::find($id);
            if ($onu) {
                try {
                    $provisioning = new ProvisioningService($onu->olt);
                    $provisioning->deleteOnu($onu->slot, $onu->pon_port, $onu->onu_id);
                } catch (\Exception $e) {
                    // Continue with deletion
                }
                $onu->delete();
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($ids) . ' ONU(s) deleted'
        ]);
    }

    public function reboot($id)
    {
        $onu = Onu::findOrFail($id);
        
        try {
            $provisioning = new ProvisioningService($onu->olt);
            $result = $provisioning->rebootOnu($onu->slot, $onu->pon_port, $onu->onu_id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'ONU reboot command sent'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkReboot(Request $request)
    {
        $ids = $request->input('ids', []);
        $results = [];

        foreach ($ids as $id) {
            $onu = Onu::find($id);
            if ($onu) {
                try {
                    $provisioning = new ProvisioningService($onu->olt);
                    $result = $provisioning->rebootOnu($onu->slot, $onu->pon_port, $onu->onu_id);
                    $results[] = ['id' => $id, 'success' => $result['success']];
                } catch (\Exception $e) {
                    $results[] = ['id' => $id, 'success' => false, 'error' => $e->getMessage()];
                }
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    public function factoryReset($id)
    {
        $onu = Onu::findOrFail($id);
        
        try {
            $provisioning = new ProvisioningService($onu->olt);
            $result = $provisioning->factoryResetOnu($onu->slot, $onu->pon_port, $onu->onu_id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'ONU factory reset command sent'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function disable($id)
    {
        $onu = Onu::findOrFail($id);
        $onu->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'ONU disabled'
        ]);
    }

    public function enable($id)
    {
        $onu = Onu::findOrFail($id);
        $onu->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'ONU enabled'
        ]);
    }

    public function getRealtimeData($id)
    {
        $onu = Onu::findOrFail($id);
        
        try {
            $snmp = new SNMPService($onu->olt);
            $onuIndex = ($onu->slot * 10000000) + ($onu->pon_port * 100000) + $onu->onu_id;
            
            $data = [
                'rx_power' => $snmp->getOnuRxPower($onuIndex),
                'tx_power' => $snmp->getOnuTxPower($onuIndex),
                'status' => $snmp->getOnuStatus($onuIndex),
                'distance' => $snmp->getOnuDistance($onuIndex),
                'temperature' => $snmp->getOnuTemperature($onuIndex),
            ];

            // Update database
            $onu->update([
                'rx_power' => $data['rx_power'],
                'tx_power' => $data['tx_power'],
                'status' => $data['status'],
                'distance' => $data['distance'],
                'temperature' => $data['temperature'],
                'last_seen' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getByOltAndPon($oltId, $slot, $port)
    {
        $onus = Onu::where('olt_id', $oltId)
            ->where('slot', $slot)
            ->where('pon_port', $port)
            ->orderBy('onu_id')
            ->get(['id', 'onu_id', 'onu_sn', 'customer_name', 'status']);

        return response()->json($onus);
    }
}
