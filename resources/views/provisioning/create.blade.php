@extends('layouts.app')

@section('title', 'Register ONU')
@section('page_title', 'Register New ONU')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">ONU Provisioning</h3>
            <p class="text-sm text-gray-600">Register a new ONU to the network</p>
        </div>
        
        <form action="{{ route('provisioning.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @if($detection)
            <input type="hidden" name="detection_id" value="{{ $detection->id }}">
            @endif
            
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">OLT</label>
                    <select name="olt_id" id="olt_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        @foreach($olts as $o)
                        <option value="{{ $o->id }}" {{ ($olt->id ?? '') == $o->id ? 'selected' : '' }}>
                            {{ $o->olt_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name</label>
                    <input type="text" name="customer_name" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2"
                           placeholder="Enter customer name">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Slot</label>
                    <input type="number" name="slot" id="slot" required 
                           value="{{ $detection->slot ?? '' }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">PON Port</label>
                    <input type="number" name="pon_port" id="pon_port" required 
                           value="{{ $detection->pon_port ?? '' }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ONU ID</label>
                    <div class="flex">
                        <input type="number" name="onu_id" id="onu_id" required 
                               value="{{ $nextOnuId ?? '' }}"
                               class="w-full border border-gray-300 rounded-l-lg px-4 py-2">
                        <button type="button" onclick="getNextOnuId()" 
                                class="bg-gray-100 hover:bg-gray-200 px-3 py-2 border border-l-0 border-gray-300 rounded-r-lg">
                            <i class="fas fa-magic"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Next available ID</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ONU SN</label>
                    <input type="text" name="onu_sn" id="onu_sn" required 
                           value="{{ $detection->onu_sn ?? '' }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ONU Type</label>
                    <select name="onu_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="F601" {{ ($detection->onu_type ?? '') == 'F601' ? 'selected' : '' }}>F601</option>
                        <option value="F660" {{ ($detection->onu_type ?? '') == 'F660' ? 'selected' : '' }}>F660</option>
                        <option value="F670" {{ ($detection->onu_type ?? '') == 'F670' ? 'selected' : '' }}>F670</option>
                        <option value="F680" {{ ($detection->onu_type ?? '') == 'F680' ? 'selected' : '' }}>F680</option>
                        <option value="F673AV9" {{ ($detection->onu_type ?? '') == 'F673AV9' ? 'selected' : '' }}>F673AV9</option>
                    </select>
                </div>
            </div>
            
            <!-- Additional Info -->
            <div class="border-t pt-6">
                <h4 class="text-md font-semibold text-gray-800 mb-4">Additional Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Customer ID</label>
                        <input type="text" name="customer_id" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="text" name="phone" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea name="address" rows="2" 
                              class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
                </div>
            </div>
            
            <!-- Service Configuration -->
            <div class="border-t pt-6">
                <h4 class="text-md font-semibold text-gray-800 mb-4">Service Configuration</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">TCONT Profile</label>
                        <select name="bandwidth_profile" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            <option value="DATA">DATA</option>
                            <option value="VOICE">VOICE</option>
                            <option value="VIDEO">VIDEO</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">User VLAN</label>
                        <input type="number" name="user_vlan" value="100" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">C-VLAN</label>
                        <input type="number" name="c_vid" value="100" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>
            </div>
            
            <!-- WAN Configuration -->
            <div class="border-t pt-6">
                <h4 class="text-md font-semibold text-gray-800 mb-4">WAN Configuration</h4>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mode</label>
                    <select name="wan_mode" id="wan_mode" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="pppoe">PPPoE</option>
                        <option value="static">Static IP</option>
                        <option value="dhcp">DHCP</option>
                    </select>
                </div>
                
                <div id="pppoe-fields" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PPPoE Username</label>
                        <input type="text" name="pppoe_username" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PPPoE Password</label>
                        <input type="password" name="pppoe_password" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>
            </div>
            
            <!-- WiFi Configuration -->
            <div class="border-t pt-6">
                <h4 class="text-md font-semibold text-gray-800 mb-4">WiFi Configuration</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">WiFi SSID</label>
                        <input type="text" name="wifi_ssid" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">WiFi Password</label>
                        <input type="password" name="wifi_password" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>
            </div>
            
            <!-- Map Coordinates -->
            <div class="border-t pt-6">
                <h4 class="text-md font-semibold text-gray-800 mb-4">Location</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                        <input type="number" step="any" name="latitude" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                        <input type="number" step="any" name="longitude" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>
            </div>
            
            <!-- Submit -->
            <div class="border-t pt-6 flex justify-end space-x-4">
                <a href="{{ route('detection.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center">
                    <i class="fas fa-save mr-2"></i> Save ONU
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Toggle PPPoE fields
const wanMode = document.getElementById('wan_mode');
const pppoeFields = document.getElementById('pppoe-fields');

wanMode?.addEventListener('change', function() {
    if (this.value === 'pppoe') {
        pppoeFields.style.display = 'grid';
    } else {
        pppoeFields.style.display = 'none';
    }
});

async function getNextOnuId() {
    const oltId = document.getElementById('olt_id').value;
    const slot = document.getElementById('slot').value;
    const port = document.getElementById('pon_port').value;
    
    if (!oltId || !slot || !port) {
        alert('Please select OLT, Slot and Port first');
        return;
    }
    
    try {
        const response = await fetch(`/api/provisioning/next-onu-id?olt_id=${oltId}&slot=${slot}&port=${port}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('onu_id').value = data.next_onu_id;
        } else {
            alert('Failed to get next ONU ID: ' + data.error);
        }
    } catch (e) {
        alert('Failed to get next ONU ID');
    }
}
</script>
@endsection
