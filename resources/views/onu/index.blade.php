@extends('layouts.app')

@section('title', 'ONU Management')
@section('page_title', 'ONU Management')

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('onu.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search customer, SN, or ID..."
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <select name="olt_id" class="border border-gray-300 rounded-lg px-4 py-2">
                <option value="">All OLTs</option>
                @foreach($olts as $olt)
                <option value="{{ $olt->id }}" {{ request('olt_id') == $olt->id ? 'selected' : '' }}>
                    {{ $olt->olt_name }}
                </option>
                @endforeach
            </select>
            <select name="status" class="border border-gray-300 rounded-lg px-4 py-2">
                <option value="">All Status</option>
                <option value="online" {{ request('status') == 'online' ? 'selected' : '' }}>Online</option>
                <option value="offline" {{ request('status') == 'offline' ? 'selected' : '' }}>Offline</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-search mr-2"></i> Search
            </button>
            <a href="{{ route('onu.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-times mr-2"></i> Clear
            </a>
        </form>
    </div>
    
    <!-- Bulk Actions -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                <label for="selectAll" class="text-sm text-gray-600">Select All</label>
                <span id="selectedCount" class="text-sm text-gray-500 hidden">(<span>0</span> selected)</span>
            </div>
            <div class="flex space-x-2">
                <button onclick="bulkReboot()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-sync mr-1"></i> Reboot
                </button>
                <button onclick="bulkDelete()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 w-8"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ONU ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serial Number</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">OLT/PON</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">RX Power</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($onus as $onu)
                    <tr class="hover:bg-gray-50" data-id="{{ $onu->id }}">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="onu-checkbox rounded border-gray-300" value="{{ $onu->id }}">
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $onu->onu_id }}</td>
                        <td class="px-4 py-3 text-gray-600 font-mono text-sm">{{ $onu->onu_sn }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $onu->customer_name }}</div>
                            @if($onu->customer_id)
                            <div class="text-xs text-gray-500">ID: {{ $onu->customer_id }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="text-gray-900">{{ $onu->olt->olt_name ?? '-' }}</div>
                            <div class="text-gray-500">{{ $onu->slot }}/{{ $onu->pon_port }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($onu->status == 'online')
                                <span class="flex items-center text-green-600">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span> Online
                                </span>
                            @else
                                <span class="flex items-center text-red-600">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span> Offline
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($onu->rx_power !== null)
                                @if($onu->rx_power >= -25)
                                    <span class="text-green-600 font-medium">{{ $onu->rx_power }} dBm</span>
                                @elseif($onu->rx_power >= -27)
                                    <span class="text-yellow-600 font-medium">{{ $onu->rx_power }} dBm</span>
                                @else
                                    <span class="text-red-600 font-medium">{{ $onu->rx_power }} dBm</span>
                                @endif
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-2">
                                <a href="{{ route('onu.show', $onu->id) }}" class="text-blue-600 hover:text-blue-800" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('onu.edit', $onu->id) }}" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="rebootOnu({{ $onu->id }})" class="text-orange-600 hover:text-orange-800" title="Reboot">
                                    <i class="fas fa-sync"></i>
                                </button>
                                <form action="{{ route('onu.destroy', $onu->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            No ONUs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $onus->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Select All checkbox
const selectAll = document.getElementById('selectAll');
const checkboxes = document.querySelectorAll('.onu-checkbox');
const selectedCount = document.getElementById('selectedCount');

selectAll?.addEventListener('change', function() {
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

checkboxes.forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const checked = document.querySelectorAll('.onu-checkbox:checked');
    if (checked.length > 0) {
        selectedCount.classList.remove('hidden');
        selectedCount.querySelector('span').textContent = checked.length;
    } else {
        selectedCount.classList.add('hidden');
    }
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.onu-checkbox:checked')).map(cb => cb.value);
}

async function rebootOnu(id) {
    if (!confirm('Reboot this ONU?')) return;
    
    try {
        const response = await fetch(`/api/onu/${id}/reboot`, {
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

async function bulkReboot() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        alert('Please select ONUs to reboot');
        return;
    }
    
    if (!confirm(`Reboot ${ids.length} ONU(s)?`)) return;
    
    try {
        const response = await fetch('/api/onu/bulk-reboot', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ ids })
        });
        const data = await response.json();
        alert(data.message);
        location.reload();
    } catch (e) {
        alert('Failed to reboot ONUs');
    }
}

async function bulkDelete() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        alert('Please select ONUs to delete');
        return;
    }
    
    if (!confirm(`Delete ${ids.length} ONU(s)? This cannot be undone!`)) return;
    
    try {
        const response = await fetch('/api/onu/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ ids })
        });
        const data = await response.json();
        alert(data.message);
        location.reload();
    } catch (e) {
        alert('Failed to delete ONUs');
    }
}
</script>
@endsection
