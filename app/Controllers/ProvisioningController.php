<?php

namespace App\Controllers;

use App\Models\Olt;
use App\Models\Onu;
use App\Models\OnuDetection;
use App\Models\PonPort;
use App\Services\ProvisioningService;
use Illuminate\Http\Request;

class ProvisioningController extends Controller
{
    public function create($detectionId = null)
    {
        $detection = null;
        $olt = null;
        $ponPort = null;
        $nextOnuId = 1;

        if ($detectionId) {
            $detection = OnuDetection::with('olt')->findOrFail($detectionId);
            $olt = $detection->olt;
            $ponPort = PonPort::where('olt_id', $olt->id)
                ->where('slot', $detection->slot)
                ->where('port', $detection->pon_port)
                ->first();
            
            if ($ponPort) {
                $nextOnuId = $ponPort->getAvailableOnuId() ?? 1;
            }
        }

        $olts = Olt::active()->get();

        return view('provisioning.create', compact('detection', 'olt', 'ponPort', 'nextOnuId', 'olts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'slot' => 'required|integer',
            'pon_port' => 'required|integer',
            'onu_id' => 'required|integer',
            'onu_sn' => 'required|string',
            'onu_type' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'customer_id' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'tcont_id' => 'nullable|integer',
            'bandwidth_profile' => 'nullable|string',
            'gemport_id' => 'nullable|integer',
            'service_port_id' => 'nullable|integer',
            'vport' => 'nullable|integer',
            'user_vlan' => 'nullable|integer',
            'c_vid' => 'nullable|integer',
            'wan_mode' => 'required|in:pppoe,static,dynamic',
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
            'static_ip' => 'nullable|ip',
            'static_gateway' => 'nullable|ip',
            'static_subnet' => 'nullable|string',
            'wifi_ssid' => 'nullable|string|max:255',
            'wifi_password' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $olt = Olt::findOrFail($validated['olt_id']);
        $ponPort = PonPort::where('olt_id', $olt->id)
            ->where('slot', $validated['slot'])
            ->where('port', $validated['pon_port'])
            ->first();

        if (!$ponPort) {
            return redirect()->back()
                ->with('error', 'PON Port not found')
                ->withInput();
        }

        // Add pon_port_id to validated data
        $validated['pon_port_id'] = $ponPort->id;

        // Auto-assign service port if not provided
        if (empty($validated['service_port_id'])) {
            $lastServicePort = Onu::where('olt_id', $olt->id)->max('service_port_template');
            $validated['service_port_id'] = ($lastServicePort ?? 0) + 1;
        }

        // Provision ONU
        $service = new ProvisioningService($olt);
        $result = $service->provisionOnu($validated);

        if ($result['success']) {
            // Update detection status if from detection
            if ($request->has('detection_id')) {
                $detection = OnuDetection::find($request->detection_id);
                if ($detection) {
                    $detection->update([
                        'status' => 'registered',
                        'registered_at' => now()
                    ]);
                }
            }

            return redirect()->route('onu.index')
                ->with('success', 'ONU provisioned successfully');
        } else {
            return redirect()->back()
                ->with('error', 'Provisioning failed: ' . $result['error'])
                ->withInput();
        }
    }

    public function autoProvision(Request $request)
    {
        $validated = $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'slot' => 'required|integer',
            'pon_port' => 'required|integer',
            'onu_sn' => 'required|string',
            'onu_type' => 'required|string',
            'customer_name' => 'required|string',
            'pppoe_username' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'wifi_ssid' => 'nullable|string',
            'wifi_password' => 'nullable|string',
        ]);

        $olt = Olt::findOrFail($validated['olt_id']);
        $service = new ProvisioningService($olt);

        $result = $service->autoProvisionOnu(
            $validated['slot'],
            $validated['pon_port'],
            $validated['onu_sn'],
            $validated['customer_name'],
            [
                'onu_type' => $validated['onu_type'],
                'pppoe_username' => $validated['pppoe_username'] ?? null,
                'pppoe_password' => $validated['pppoe_password'] ?? null,
                'wifi_ssid' => $validated['wifi_ssid'] ?? null,
                'wifi_password' => $validated['wifi_password'] ?? null,
            ]
        );

        return response()->json($result);
    }

    public function getNextOnuId(Request $request)
    {
        $oltId = $request->input('olt_id');
        $slot = $request->input('slot');
        $port = $request->input('port');

        $ponPort = PonPort::where('olt_id', $oltId)
            ->where('slot', $slot)
            ->where('port', $port)
            ->first();

        if ($ponPort) {
            $nextId = $ponPort->getAvailableOnuId();
            return response()->json([
                'success' => true,
                'next_onu_id' => $nextId
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'PON Port not found'
        ], 404);
    }

    public function configureOnu($id)
    {
        $onu = Onu::with(['olt', 'ponPort'])->findOrFail($id);
        
        return view('provisioning.configure', compact('onu'));
    }

    public function saveConfiguration(Request $request, $id)
    {
        $onu = Onu::findOrFail($id);

        $validated = $request->validate([
            'tcont_profile' => 'nullable|string',
            'gemport_template' => 'nullable|string',
            'vlan_profile' => 'nullable|integer',
            'service_port_template' => 'nullable|integer',
            'pppoe_username' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'wifi_ssid' => 'nullable|string',
            'wifi_password' => 'nullable|string',
        ]);

        // Encrypt passwords
        if (!empty($validated['pppoe_password'])) {
            $validated['pppoe_password'] = encrypt($validated['pppoe_password']);
        }
        if (!empty($validated['wifi_password'])) {
            $validated['wifi_password'] = encrypt($validated['wifi_password']);
        }

        $onu->update($validated);

        // Apply configuration to OLT
        try {
            $service = new ProvisioningService($onu->olt);
            
            if (!empty($validated['wifi_ssid'])) {
                $service->configureWifi(
                    $onu->slot,
                    $onu->pon_port,
                    $onu->onu_id,
                    $validated['wifi_ssid'],
                    $request->wifi_password ?? ''
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuration saved and applied'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'Configuration saved to database but failed to apply: ' . $e->getMessage()
            ]);
        }
    }
}
