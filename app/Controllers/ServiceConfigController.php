<?php

namespace App\Controllers;

use App\Models\TcontProfile;
use App\Models\BandwidthProfile;
use App\Models\GemportTemplate;
use App\Models\ServicePortTemplate;
use App\Models\VlanProfile;
use Illuminate\Http\Request;

class ServiceConfigController extends Controller
{
    public function index()
    {
        $tcontProfiles = TcontProfile::where('is_active', true)->get();
        $bandwidthProfiles = BandwidthProfile::where('is_active', true)->get();
        $gemportTemplates = GemportTemplate::where('is_active', true)->get();
        $servicePortTemplates = ServicePortTemplate::where('is_active', true)->get();
        $vlanProfiles = VlanProfile::where('is_active', true)->get();

        return view('service-config.index', compact(
            'tcontProfiles',
            'bandwidthProfiles',
            'gemportTemplates',
            'servicePortTemplates',
            'vlanProfiles'
        ));
    }

    // TCONT Profiles
    public function storeTcont(Request $request)
    {
        $validated = $request->validate([
            'profile_name' => 'required|string|unique:tcont_profiles',
            'tcont_id' => 'required|integer',
            'bandwidth_profile' => 'required|string',
            'description' => 'nullable|string'
        ]);

        TcontProfile::create($validated);
        return redirect()->back()->with('success', 'TCONT profile created');
    }

    // Bandwidth Profiles
    public function storeBandwidth(Request $request)
    {
        $validated = $request->validate([
            'profile_name' => 'required|string|unique:bandwidth_profiles',
            'profile_type' => 'required|in:fixed,assured,maximum',
            'fixed_bw' => 'nullable|integer',
            'assure_bw' => 'nullable|integer',
            'max_bw' => 'required|integer',
            'description' => 'nullable|string'
        ]);

        BandwidthProfile::create($validated);
        return redirect()->back()->with('success', 'Bandwidth profile created');
    }

    // GEM Port Templates
    public function storeGemport(Request $request)
    {
        $validated = $request->validate([
            'template_name' => 'required|string|unique:gemport_templates',
            'gemport_id' => 'required|integer',
            'tcont_profile' => 'required|string',
            'traffic_class' => 'required|string',
            'description' => 'nullable|string'
        ]);

        GemportTemplate::create($validated);
        return redirect()->back()->with('success', 'GEM port template created');
    }

    // Service Port Templates
    public function storeServicePort(Request $request)
    {
        $validated = $request->validate([
            'template_name' => 'required|string|unique:service_port_templates',
            'service_port_id' => 'required|integer',
            'vport' => 'required|integer',
            'user_vlan' => 'required|integer',
            'c_vid' => 'nullable|integer',
            'vlan_mode' => 'required|string',
            'translation_mode' => 'required|string',
            'description' => 'nullable|string'
        ]);

        ServicePortTemplate::create($validated);
        return redirect()->back()->with('success', 'Service port template created');
    }

    // VLAN Profiles
    public function storeVlan(Request $request)
    {
        $validated = $request->validate([
            'profile_name' => 'required|string|unique:vlan_profiles',
            'vlan_id' => 'required|integer',
            'vlan_name' => 'nullable|string',
            'vlan_type' => 'required|in:residential,business,management',
            'description' => 'nullable|string'
        ]);

        VlanProfile::create($validated);
        return redirect()->back()->with('success', 'VLAN profile created');
    }

    // API Methods
    public function apiProfiles()
    {
        return response()->json([
            'tcont' => TcontProfile::active()->get(),
            'bandwidth' => BandwidthProfile::active()->get(),
            'gemport' => GemportTemplate::active()->get(),
            'service_port' => ServicePortTemplate::active()->get(),
            'vlan' => VlanProfile::active()->get()
        ]);
    }
}
