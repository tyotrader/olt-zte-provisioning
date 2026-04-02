@extends('layouts.app')

@section('title', 'OLT Management')
@section('page_title', 'OLT Management')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800">OLT List</h3>
        <a href="{{ route('olt.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i> Add OLT
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ONU Count</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($olts as $olt)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $olt->olt_name }}</div>
                        <div class="text-sm text-gray-500">{{ $olt->location }}</div>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $olt->ip_address }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">{{ $olt->olt_model }}</span>
                    </td>
                    <td class="px-6 py-4">
                        @if($olt->status == 'online')
                            <span class="flex items-center text-green-600">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span> Online
                            </span>
                        @elseif($olt->status == 'offline')
                            <span class="flex items-center text-red-600">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span> Offline
                            </span>
                        @else
                            <span class="flex items-center text-gray-600">
                                <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span> Unknown
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-medium">{{ $olt->onus_count }}</span>
                        <span class="text-sm text-gray-500">/ {{ $olt->pon_ports_count * 128 }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            <button onclick="testConnection({{ $olt->id }})" 
                                    class="text-blue-600 hover:text-blue-800" title="Test Connection">
                                <i class="fas fa-plug"></i>
                            </button>
                            <a href="{{ route('olt.edit', $olt->id) }}" 
                               class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('olt.destroy', $olt->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Are you sure you want to delete this OLT?');">
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
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No OLTs found. <a href="{{ route('olt.create') }}" class="text-blue-600 hover:underline">Add one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $olts->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script>
async function testConnection(oltId) {
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    try {
        const response = await fetch(`/api/olt/${oltId}/test-connection`);
        const data = await response.json();
        
        if (data.success) {
            alert(`Connection successful!\nTelnet: ${data.telnet ? 'OK' : 'Failed'}\nSNMP: ${data.snmp ? 'OK' : 'Failed'}`);
            location.reload();
        } else {
            alert('Connection failed: ' + (data.error || 'Unknown error'));
        }
    } catch (e) {
        alert('Connection test failed: ' + e.message);
    } finally {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}
</script>
@endsection
