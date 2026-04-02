@extends('layouts.app')

@section('title', 'ONU Detail - ' . $onu->customer_name)
@section('page_title', 'ONU Detail')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $onu->customer_name }}</h2>
                <p class="text-gray-600 mt-1">{{ $onu->address }}</p>
                <div class="flex items-center mt-2 space-x-4">
                    <span class="px-3 py-1 rounded-full text-sm {{ $onu->status == 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($onu->status) }}
                    </span>
                    <span class="text-sm text-gray-500">
                        <i class="fas fa-clock mr-1"></i> Last seen: {{ $onu->last_seen ? $onu->last_seen->diffForHumans() : 'Never' }}
                    </span>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('onu.edit', $onu->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-edit mr-2"></i> Edit
                </a>
                <button onclick="rebootOnu()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-sync mr-2"></i> Reboot
                </button>
            </div>
        </div>
    </div>
    
    <!-- Realtime Stats -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Realtime Data</h3>
            <span class="text-xs text-gray-500 flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                Auto-refresh 0.5s
            </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="realtimeStats">
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-sm text-gray-600">RX Power</p>
                <p class="text-2xl font-bold {{ $onu->rx_power >= -25 ? 'text-green-600' : ($onu->rx_power >= -27 ? 'text-yellow-600' : 'text-red-600') }}" id="rxPower">
                    {{ $onu->rx_power ?? '-' }} <span class="text-sm">dBm</span>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-sm text-gray-600">TX Power</p>
                <p class="text-2xl font-bold text-blue-600" id="txPower">
                    {{ $onu->tx_power ?? '-' }} <span class="text-sm">dBm</span>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-sm text-gray-600">Distance</p>
                <p class="text-2xl font-bold text-purple-600" id="distance">
                    {{ $onu->distance ?? '-' }} <span class="text-sm">km</span>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-sm text-gray-600">Temperature</p>
                <p class="text-2xl font-bold text-orange-600" id="temperature">
                    {{ $onu->temperature ?? '-' }} <span class="text-sm">°C</span>
                </p>
            </div>
        </div>
        
        <!-- Traffic Chart -->
        <div class="mt-6">
            <h4 class="text-md font-medium text-gray-700 mb-2">Traffic</h4>
            <canvas id="trafficChart" height="100"></canvas>
        </div>
    </div>
    
    <!-- ONU Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Basic Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">ONU ID:</span>
                    <span class="font-medium">{{ $onu->onu_id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Serial Number:</span>
                    <span class="font-mono">{{ $onu->onu_sn }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Type:</span>
                    <span>{{ $onu->onu_type }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Customer ID:</span>
                    <span>{{ $onu->customer_id ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Phone:</span>
                    <span>{{ $onu->phone ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Registered:</span>
                    <span>{{ $onu->registered_at->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>
        
        <!-- Network Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Network Information</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">OLT:</span>
                    <span>{{ $onu->olt->olt_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">PON Port:</span>
                    <span>{{ $onu->slot }}/{{ $onu->pon_port }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">ODP:</span>
                    <span>{{ $onu->odp->odp_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">WAN Mode:</span>
                    <span class="uppercase">{{ $onu->wan_mode }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">VLAN:</span>
                    <span>{{ $onu->vlan_profile ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Firmware:</span>
                    <span>{{ $onu->firmware_version ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Configuration -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Configuration</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h4 class="text-md font-medium text-gray-700 mb-2">WAN Configuration</h4>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mode:</span>
                        <span class="uppercase">{{ $onu->wan_mode }}</span>
                    </div>
                    @if($onu->wan_mode == 'pppoe')
                    <div class="flex justify-between">
                        <span class="text-gray-600">Username:</span>
                        <span>{{ $onu->pppoe_username ?? '-' }}</span>
                    </div>
                    @elseif($onu->wan_mode == 'static')
                    <div class="flex justify-between">
                        <span class="text-gray-600">IP Address:</span>
                        <span>{{ $onu->static_ip ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Gateway:</span>
                        <span>{{ $onu->static_gateway ?? '-' }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div>
                <h4 class="text-md font-medium text-gray-700 mb-2">WiFi Configuration</h4>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span>{{ $onu->wifi_enabled ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                    @if($onu->wifi_enabled)
                    <div class="flex justify-between">
                        <span class="text-gray-600">SSID:</span>
                        <span>{{ $onu->wifi_ssid ?? '-' }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Location -->
    @if($onu->latitude && $onu->longitude)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Location</h3>
        <div id="map" style="height: 300px; border-radius: 8px;"></div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script>
let trafficChart;

// Initialize traffic chart
function initTrafficChart() {
    const ctx = document.getElementById('trafficChart').getContext('2d');
    trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'RX',
                data: [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'TX',
                data: [],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatBytes(value);
                        }
                    }
                }
            }
        }
    });
}

// Update realtime data
async function updateRealtimeData() {
    try {
        const response = await fetch(`/api/onu/{{ $onu->id }}/realtime`);
        const data = await response.json();
        
        if (data.success) {
            // Update stats
            document.getElementById('rxPower').innerHTML = (data.data.rx_power ?? '-') + ' <span class="text-sm">dBm</span>';
            document.getElementById('txPower').innerHTML = (data.data.tx_power ?? '-') + ' <span class="text-sm">dBm</span>';
            document.getElementById('distance').innerHTML = (data.data.distance ?? '-') + ' <span class="text-sm">km</span>';
            document.getElementById('temperature').innerHTML = (data.data.temperature ?? '-') + ' <span class="text-sm">°C</span>';
            
            // Update RX Power color
            const rxEl = document.getElementById('rxPower');
            rxEl.className = 'text-2xl font-bold ' + 
                (data.data.rx_power >= -25 ? 'text-green-600' : 
                 data.data.rx_power >= -27 ? 'text-yellow-600' : 'text-red-600');
        }
    } catch (e) {
        console.error('Failed to update realtime data:', e);
    }
}

// Reboot ONU
async function rebootOnu() {
    if (!confirm('Are you sure you want to reboot this ONU?')) return;
    
    try {
        const response = await fetch(`/api/onu/{{ $onu->id }}/reboot`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await response.json();
        alert(data.message || data.error);
    } catch (e) {
        alert('Failed to reboot ONU');
    }
}

// Initialize map
function initMap() {
    @if($onu->latitude && $onu->longitude)
    const map = L.map('map').setView([{{ $onu->latitude }}, {{ $onu->longitude }}], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    L.marker([{{ $onu->latitude }}, {{ $onu->longitude }}])
        .addTo(map)
        .bindPopup('{{ $onu->customer_name }}')
        .openPopup();
    @endif
}

// Initialize
initTrafficChart();
initMap();
updateRealtimeData();

// Auto refresh
setInterval(updateRealtimeData, 5000);
</script>
@endsection
