@extends('layouts.app')

@section('title', 'Fiber Topology Map')
@section('page_title', 'Fiber Topology Map')

@section('styles')
<style>
#map { height: 600px; border-radius: 8px; }
.leaflet-popup-content { margin: 10px; }
.node-olt { color: #2563eb; }
.node-odp { color: #eab308; }
.node-onu { color: #10b981; }
.node-onu-offline { color: #ef4444; }
</style>
@endsection

@section('content')
<div class="space-y-4">
    <!-- Controls -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-[300px]">
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search customer, SN, OLT, ODP..."
                           class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2">
                    <span class="absolute left-3 top-2.5 text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="showOlt" checked class="rounded">
                    <span class="text-sm"><i class="fas fa-server text-blue-600"></i> OLT</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="showOdp" checked class="rounded">
                    <span class="text-sm"><i class="fas fa-box text-yellow-600"></i> ODP</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="showOnu" checked class="rounded">
                    <span class="text-sm"><i class="fas fa-plug text-green-600"></i> ONU</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="showLines" checked class="rounded">
                    <span class="text-sm"><i class="fas fa-link"></i> Links</span>
                </label>
            </div>
            <div class="flex space-x-2">
                <button onclick="refreshMap()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-sync"></i>
                </button>
                <button onclick="exportMap()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-download"></i>
                </button>
            </div>
        </div>
        <div id="searchResults" class="hidden mt-2 bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto"></div>
    </div>
    
    <!-- Map -->
    <div class="bg-white rounded-lg shadow p-4">
        <div id="map"></div>
    </div>
    
    <!-- Legend -->
    <div class="bg-white rounded-lg shadow p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">Legend</h4>
        <div class="flex flex-wrap gap-6 text-sm">
            <span class="flex items-center"><span class="w-3 h-3 bg-blue-600 rounded-full mr-2"></span> OLT (Online)</span>
            <span class="flex items-center"><span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span> ODP</span>
            <span class="flex items-center"><span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span> ONU (Online)</span>
            <span class="flex items-center"><span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span> ONU (Offline)</span>
            <span class="flex items-center"><span class="w-3 h-3 bg-orange-500 rounded-full mr-2"></span> ONU (Low Signal)</span>
            <span class="flex items-center"><span class="w-8 h-0.5 bg-green-500 mr-2"></span> Active Link</span>
            <span class="flex items-center"><span class="w-8 h-0.5 bg-red-500 mr-2"></span> Problem Link</span>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let map;
let markers = {};
let polylines = [];
let layers = { olt: [], odp: [], onu: [], lines: [] };

// Initialize map
document.addEventListener('DOMContentLoaded', function() {
    map = L.map('map').setView([-6.2, 106.8], 12); // Default to Jakarta
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    loadMapData();
    
    // Setup search
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => searchNodes(this.value), 300);
    });
    
    // Layer toggles
    document.getElementById('showOlt')?.addEventListener('change', toggleLayers);
    document.getElementById('showOdp')?.addEventListener('change', toggleLayers);
    document.getElementById('showOnu')?.addEventListener('change', toggleLayers);
    document.getElementById('showLines')?.addEventListener('change', toggleLayers);
});

async function loadMapData() {
    try {
        const response = await fetch('/api/map/data');
        const data = await response.json();
        
        renderMap(data);
    } catch (e) {
        console.error('Failed to load map data:', e);
    }
}

function renderMap(data) {
    // Clear existing
    Object.values(markers).forEach(m => map.removeLayer(m));
    polylines.forEach(p => map.removeLayer(p));
    markers = {};
    polylines = [];
    layers = { olt: [], odp: [], onu: [], lines: [] };
    
    // Add markers
    data.markers.forEach(marker => {
        let icon, color;
        
        if (marker.type === 'olt') {
            icon = 'server';
            color = '#2563eb';
        } else if (marker.type === 'odp') {
            icon = 'box';
            color = '#eab308';
        } else {
            icon = 'plug';
            if (marker.status === 'online') {
                color = marker.signal_status === 'warning' ? '#f97316' : '#10b981';
            } else {
                color = '#ef4444';
            }
        }
        
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<i class="fas fa-${icon}" style="color: ${color}; font-size: 20px; text-shadow: 0 0 3px white;"></i>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });
        
        if (marker.lat && marker.lng) {
            const m = L.marker([marker.lat, marker.lng], { icon: customIcon })
                .bindPopup(createPopupContent(marker))
                .addTo(map);
            
            markers[marker.id] = m;
            layers[marker.type].push(m);
        }
    });
    
    // Add lines
    data.lines.forEach(line => {
        if (line.coords && line.coords.length === 2) {
            const color = line.status === 'offline' ? '#ef4444' : '#10b981';
            const polyline = L.polyline(line.coords, {
                color: color,
                weight: 2,
                opacity: 0.7,
                dashArray: line.dashed ? '5, 5' : null
            }).addTo(map);
            
            polylines.push(polyline);
            layers.lines.push(polyline);
        }
    });
    
    // Fit bounds if we have markers
    if (Object.keys(markers).length > 0) {
        const group = new L.featureGroup(Object.values(markers));
        map.fitBounds(group.getBounds().pad(0.1));
    }
    
    toggleLayers();
}

function createPopupContent(marker) {
    let content = `<div class="text-sm">`;
    content += `<strong class="text-lg">${marker.name}</strong><br>`;
    content += `<span class="text-gray-500 capitalize">${marker.type}</span><hr class="my-2">`;
    
    if (marker.type === 'olt') {
        content += `IP: ${marker.data.ip}<br>`;
        content += `Model: ${marker.data.model}<br>`;
        content += `Total PON: ${marker.data.total_pon}<br>`;
        content += `Total ONU: ${marker.data.total_onu}<br>`;
        content += `Online: ${marker.data.online_onu}`;
    } else if (marker.type === 'odp') {
        content += `OLT: ${marker.data.olt_name}<br>`;
        content += `Connected ONU: ${marker.data.connected_onu}<br>`;
        content += `Ports: ${marker.data.used_ports}/${marker.data.total_ports}`;
    } else {
        content += `SN: ${marker.data.sn}<br>`;
        content += `ONU ID: ${marker.data.onu_id}<br>`;
        content += `OLT: ${marker.data.olt_name}<br>`;
        content += `PON: ${marker.data.pon_port}<br>`;
        if (marker.data.rx_power) content += `RX: ${marker.data.rx_power} dBm<br>`;
        if (marker.data.distance) content += `Distance: ${marker.data.distance} km<br>`;
        content += `Status: <span class="${marker.data.status === 'online' ? 'text-green-600' : 'text-red-600'}">${marker.data.status}</span><br>`;
        content += `<a href="/onu/${marker.id.replace('onu_', '')}" class="text-blue-600 hover:underline mt-2 inline-block">View Details</a>`;
    }
    
    content += `</div>`;
    return content;
}

function toggleLayers() {
    const showOlt = document.getElementById('showOlt').checked;
    const showOdp = document.getElementById('showOdp').checked;
    const showOnu = document.getElementById('showOnu').checked;
    const showLines = document.getElementById('showLines').checked;
    
    layers.olt.forEach(m => showOlt ? map.addLayer(m) : map.removeLayer(m));
    layers.odp.forEach(m => showOdp ? map.addLayer(m) : map.removeLayer(m));
    layers.onu.forEach(m => showOnu ? map.addLayer(m) : map.removeLayer(m));
    layers.lines.forEach(l => showLines ? map.addLayer(l) : map.removeLayer(l));
}

async function searchNodes(query) {
    const resultsDiv = document.getElementById('searchResults');
    
    if (!query || query.length < 2) {
        resultsDiv.classList.add('hidden');
        return;
    }
    
    try {
        const response = await fetch(`/api/map/search?q=${encodeURIComponent(query)}`);
        const results = await response.json();
        
        if (results.length === 0) {
            resultsDiv.innerHTML = '<div class="p-3 text-gray-500">No results found</div>';
        } else {
            resultsDiv.innerHTML = results.map(r => `
                <div class="p-3 hover:bg-gray-100 cursor-pointer border-b" onclick="flyToNode('${r.type}', ${r.lat}, ${r.lng})">
                    <span class="font-medium">${r.name}</span>
                    <span class="text-xs text-gray-500 ml-2 capitalize">(${r.type})</span>
                    ${r.sn ? `<div class="text-xs text-gray-400">SN: ${r.sn}</div>` : ''}
                </div>
            `).join('');
        }
        
        resultsDiv.classList.remove('hidden');
    } catch (e) {
        console.error('Search failed:', e);
    }
}

function flyToNode(type, lat, lng) {
    document.getElementById('searchResults').classList.add('hidden');
    if (lat && lng) {
        map.flyTo([lat, lng], 18);
    }
}

function refreshMap() {
    loadMapData();
}

function exportMap() {
    window.open('/api/map/export', '_blank');
}

// Realtime updates
setInterval(loadMapData, 30000); // Refresh every 30 seconds
</script>
@endsection
