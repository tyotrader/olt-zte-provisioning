<?php

namespace App\Controllers;

use App\Models\Odp;
use App\Models\Olt;
use App\Models\PonPort;
use Illuminate\Http\Request;

class OdpController extends Controller
{
    public function index()
    {
        $odps = Odp::with(['olt', 'ponPort', 'onus'])
            ->orderBy('odp_name')
            ->paginate(50);

        return view('odp.index', compact('odps'));
    }

    public function create()
    {
        $olts = Olt::active()->get();
        return view('odp.create', compact('olts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'odp_name' => 'required|string|max:255|unique:odps',
            'olt_id' => 'required|exists:olts,id',
            'pon_port_id' => 'nullable|exists:pon_ports,id',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'total_ports' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $validated['used_ports'] = 0;

        $odp = Odp::create($validated);

        return redirect()->route('odp.index')
            ->with('success', 'ODP created successfully');
    }

    public function edit($id)
    {
        $odp = Odp::findOrFail($id);
        $olts = Olt::active()->get();
        $ponPorts = PonPort::where('olt_id', $odp->olt_id)->get();

        return view('odp.edit', compact('odp', 'olts', 'ponPorts'));
    }

    public function update(Request $request, $id)
    {
        $odp = Odp::findOrFail($id);

        $validated = $request->validate([
            'odp_name' => 'required|string|max:255|unique:odps,odp_name,' . $id,
            'olt_id' => 'required|exists:olts,id',
            'pon_port_id' => 'nullable|exists:pon_ports,id',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'total_ports' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'required|string|in:active,inactive,maintenance',
        ]);

        $odp->update($validated);

        return redirect()->route('odp.index')
            ->with('success', 'ODP updated successfully');
    }

    public function destroy($id)
    {
        $odp = Odp::findOrFail($id);
        $odp->delete();

        return redirect()->route('odp.index')
            ->with('success', 'ODP deleted successfully');
    }

    public function getByOlt($oltId)
    {
        $odps = Odp::where('olt_id', $oltId)
            ->withCount('onus')
            ->get();

        return response()->json($odps);
    }

    public function getAvailablePort($odpId)
    {
        $odp = Odp::with('onus')->findOrFail($odpId);
        
        $usedPorts = $odp->onus->pluck('odp_port')->toArray();
        $availablePorts = [];
        
        for ($i = 1; $i <= $odp->total_ports; $i++) {
            if (!in_array($i, $usedPorts)) {
                $availablePorts[] = $i;
            }
        }

        return response()->json([
            'odp' => $odp,
            'used_ports' => $usedPorts,
            'available_ports' => $availablePorts,
            'availability' => $odp->availability_percentage
        ]);
    }

    public function apiIndex()
    {
        return response()->json(Odp::with('olt:id,olt_name')->get());
    }
}
