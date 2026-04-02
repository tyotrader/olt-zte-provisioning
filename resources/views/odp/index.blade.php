@extends('layouts.app')

@section('title', 'ODP Management')
@section('page_title', 'ODP Management')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">ODP List</h3>
            <a href="{{ route('odp.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Add ODP
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ODP Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OLT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ports</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($odps as $odp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $odp->odp_name }}</div>
                            @if($odp->address)
                            <div class="text-sm text-gray-500">{{ $odp->address }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $odp->olt->olt_name ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $odp->location ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="font-medium">{{ $odp->total_ports }}</span>
                            <span class="text-sm text-gray-500">ports</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $odp->availability_percentage }}%"></div>
                                </div>
                                <span class="text-sm text-gray-600">{{ $odp->used_ports }}/{{ $odp->total_ports }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($odp->status == 'active')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Active</span>
                            @elseif($odp->status == 'maintenance')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">Maintenance</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('odp.edit', $odp->id) }}" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('odp.destroy', $odp->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this ODP?');">
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
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No ODPs found. <a href="{{ route('odp.create') }}" class="text-blue-600 hover:underline">Add one</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $odps->links() }}
        </div>
    </div>
</div>
@endsection
