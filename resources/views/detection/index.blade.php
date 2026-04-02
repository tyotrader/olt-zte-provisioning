@extends('layouts.app')

@section('title', 'ONU Detection')
@section('page_title', 'ONU Detection (Unregistered)')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Detected</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_detected'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-search text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Ignored</p>
                    <p class="text-2xl font-bold text-gray-600">{{ $stats['ignored'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-ban text-gray-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Today Detected</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['today_detected'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-day text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scan Controls -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap gap-4 items-center">
            <select id="scanOlt" class="border border-gray-300 rounded-lg px-4 py-2">
                <option value="">All OLTs</option>
                @foreach($olts as $olt)
                <option value="{{ $olt->id }}">{{ $olt->olt_name }}</option>
                @endforeach
            </select>
            <button onclick="scanNow()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center">
                <i class="fas fa-sync mr-2"></i> Scan Now
            </button>
            <div class="flex items-center ml-auto">
                <span class="text-sm text-gray-600 mr-2">Auto Scan:</span>
                <span class="text-sm text-green-600 font-medium">Enabled (60s)</span>
            </div>
        </div>
    </div>
    
    <!-- Detection Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Unregistered ONU List</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OLT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slot/Port</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ONU SN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">LOID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discovered</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($detections as $detection)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $detection->olt->olt_name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-gray-100 rounded text-sm">{{ $detection->slot }}/{{ $detection->pon_port }}</span>
                        </td>
                        <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ $detection->onu_sn }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">{{ $detection->onu_type }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $detection->loid ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $detection->discovery_time->diffForHumans() }}</td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('provisioning.create', ['detection' => $detection->id]) }}" 
                                   class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                    <i class="fas fa-plus mr-1"></i> Register
                                </a>
                                <button onclick="ignoreOnu({{ $detection->id }})" 
                                        class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                    <i class="fas fa-ban mr-1"></i> Ignore
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No unregistered ONUs detected.
                            <button onclick="scanNow()" class="text-blue-600 hover:underline ml-2">Scan now</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $detections->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
async function scanNow() {
    const btn = event.target.closest('button');
    const oltId = document.getElementById('scanOlt').value;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Scanning...';
    
    try {
        const url = oltId ? `/api/detection/scan?olt_id=${oltId}` : '/api/detection/scan';
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await response.json();
        
        if (data.success) {
            alert(`Scan complete! Found ${data.count} new ONUs.`);
            location.reload();
        } else {
            alert('Scan failed: ' + (data.error || 'Unknown error'));
        }
    } catch (e) {
        alert('Scan failed: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync mr-2"></i> Scan Now';
    }
}

async function ignoreOnu(id) {
    if (!confirm('Ignore this ONU?')) return;
    
    try {
        const response = await fetch(`/api/detection/${id}/ignore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        }
    } catch (e) {
        alert('Failed to ignore ONU');
    }
}

// Auto refresh every 60 seconds
setInterval(() => {
    location.reload();
}, 60000);
</script>
@endsection
