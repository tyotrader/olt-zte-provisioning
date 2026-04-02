<?php

namespace App\Controllers;

use App\Models\Olt;
use App\Models\PonPort;
use App\Services\TelnetService;
use App\Services\SNMPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class OltController extends Controller
{
    public function index()
    {
        $olts = Olt::withCount('onus', 'ponPorts', 'odps')
            ->orderBy('olt_name')
            ->paginate(20);
        
        return view('olt.index', compact('olts'));
    }

    public function create()
    {
        return view('olt.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'olt_name' => 'required|string|max:255|unique:olts',
            'ip_address' => 'required|ip|unique:olts',
            'olt_model' => 'required|string|in:C300,C320,C600,ZXA10',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'snmp_community' => 'required|string|max:255',
            'snmp_read_community' => 'nullable|string|max:255',
            'snmp_write_community' => 'nullable|string|max:255',
            'snmp_port' => 'required|integer|default:161',
            'snmp_version' => 'required|string|in:v1,v2c,v3',
            'telnet_username' => 'required|string|max:255',
            'telnet_password' => 'required|string',
            'telnet_port' => 'required|integer|default:23',
            'timeout' => 'integer|default:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // Encrypt password
        $validated['telnet_password'] = Crypt::encrypt($validated['telnet_password']);

        $olt = Olt::create($validated);

        // Discover PON ports automatically
        $this->discoverPonPorts($olt);

        return redirect()->route('olt.index')
            ->with('success', 'OLT created successfully');
    }

    public function edit($id)
    {
        $olt = Olt::findOrFail($id);
        return view('olt.edit', compact('olt'));
    }

    public function update(Request $request, $id)
    {
        $olt = Olt::findOrFail($id);

        $validated = $request->validate([
            'olt_name' => 'required|string|max:255|unique:olts,olt_name,' . $id,
            'ip_address' => 'required|ip|unique:olts,ip_address,' . $id,
            'olt_model' => 'required|string|in:C300,C320,C600,ZXA10',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'snmp_community' => 'required|string|max:255',
            'snmp_port' => 'required|integer',
            'telnet_username' => 'required|string|max:255',
            'telnet_port' => 'required|integer',
            'timeout' => 'integer',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        if ($request->filled('telnet_password')) {
            $validated['telnet_password'] = Crypt::encrypt($request->telnet_password);
        }

        $olt->update($validated);

        return redirect()->route('olt.index')
            ->with('success', 'OLT updated successfully');
    }

    public function destroy($id)
    {
        $olt = Olt::findOrFail($id);
        $olt->delete();

        return redirect()->route('olt.index')
            ->with('success', 'OLT deleted successfully');
    }

    public function testConnection($id)
    {
        $olt = Olt::findOrFail($id);

        try {
            // Test Telnet
            $telnet = new TelnetService($olt);
            $telnetConnected = $telnet->connect();
            if ($telnetConnected) {
                $telnet->disconnect();
            }

            // Test SNMP
            $snmp = new SNMPService($olt);
            $snmpConnected = $snmp->testConnection();

            $olt->update(['status' => ($telnetConnected && $snmpConnected) ? 'online' : 'partial']);

            return response()->json([
                'success' => true,
                'telnet' => $telnetConnected,
                'snmp' => $snmpConnected,
                'message' => $telnetConnected && $snmpConnected 
                    ? 'All connections successful' 
                    : 'Some connections failed'
            ]);
        } catch (\Exception $e) {
            $olt->update(['status' => 'offline']);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPonPorts($id)
    {
        $olt = Olt::findOrFail($id);
        $ponPorts = PonPort::where('olt_id', $id)
            ->withCount('onus')
            ->orderBy('slot')
            ->orderBy('port')
            ->get();

        return response()->json([
            'olt' => $olt,
            'pon_ports' => $ponPorts
        ]);
    }

    public function getOnus($id)
    {
        $olt = Olt::findOrFail($id);
        $onus = $olt->onus()
            ->with('ponPort')
            ->orderBy('slot')
            ->orderBy('pon_port')
            ->orderBy('onu_id')
            ->paginate(50);

        return response()->json([
            'olt' => $olt,
            'onus' => $onus
        ]);
    }

    public function getStats($id)
    {
        $olt = Olt::findOrFail($id);
        
        try {
            $snmp = new SNMPService($olt);
            $status = $snmp->getOltStatus();
            $sysInfo = $snmp->getSystemInfo();
            $uplink = $snmp->getUplinkTraffic(1);

            return response()->json([
                'success' => true,
                'system' => $sysInfo,
                'status' => $status,
                'uplink' => $uplink,
                'onus' => [
                    'total' => $olt->onus()->count(),
                    'online' => $olt->onus()->online()->count(),
                    'offline' => $olt->onus()->offline()->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function discoverPonPorts(Olt $olt)
    {
        // Create default PON ports based on OLT model
        $portConfig = [
            'C300' => ['slots' => [1, 2], 'ports_per_slot' => 8],
            'C320' => ['slots' => [1], 'ports_per_slot' => 4],
            'C600' => ['slots' => [1, 2, 3, 4], 'ports_per_slot' => 16],
            'ZXA10' => ['slots' => [1, 2], 'ports_per_slot' => 8]
        ];

        $config = $portConfig[$olt->olt_model] ?? $portConfig['C300'];

        foreach ($config['slots'] as $slot) {
            for ($port = 1; $port <= $config['ports_per_slot']; $port++) {
                PonPort::create([
                    'olt_id' => $olt->id,
                    'slot' => $slot,
                    'port' => $port,
                    'pon_type' => 'GPON',
                    'max_onu' => 128,
                    'admin_status' => 'up',
                    'oper_status' => 'up'
                ]);
            }
        }
    }

    public function apiIndex()
    {
        return response()->json(Olt::active()->get());
    }
}
