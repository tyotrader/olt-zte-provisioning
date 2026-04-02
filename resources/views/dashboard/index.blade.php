@extends('layouts.app')

@section('title', 'Dashboard - ZTE OLT Provisioning System')
@section('page_title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total OLT</p>
                    <p class="text-3xl font-bold text-gray-900" id="totalOlts">{{ $stats['total_olts'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-server text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total ONU</p>
                    <p class="text-3xl font-bold text-gray-900" id="totalOnus">{{ $stats['total_onus'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-plug text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm">
                <span class="text-green-600 font-medium">{{ $stats['online_onus'] }} Online</span>
                <span class="mx-2 text-gray-400">|</span>
                <span class="text-red-600 font-medium">{{ $stats['offline_onus'] }} Offline</span>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Unregistered</p>
                    <p class="text-3xl font-bold text-gray-900" id="unregisteredOnus">{{ $stats['unregistered_onus'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-2">
                <a href="{{ route('detection.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                    View detections <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">PON Utilization</p>
                    <p class="text-3xl font-bold text-gray-900" id="ponUtilization">{{ $stats['pon_utilization'] }}%</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-pie text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $stats['pon_utilization'] }}%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Real-time OLT Status -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">OLT Realtime Status</h3>
            <span class="text-xs text-gray-500 flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                Live Updates
            </span>
        </div>
        <div class="p-6">
            <div id="oltStatusGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Will be populated by JavaScript -->
                <div class="text-center text-gray-500 py-8">Loading OLT status...</div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">ONU Status Distribution</h3>
            <canvas id="onuStatusChart" height="200"></canvas>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Traffic Trend (24h)</h3>
            <canvas id="trafficChart" height="200"></canvas>
        </div>
    </div>
    
    <!-- PON Utilization Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">PON Utilization by OLT</h3>
        <canvas id="ponUtilChart" height="80"></canvas>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Initialize charts
let onuStatusChart, trafficChart, ponUtilChart;

function initCharts() {
    // ONU Status Chart
    const onuStatusCtx = document.getElementById('onuStatusChart').getContext('2d');
    onuStatusChart = new Chart(onuStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Online', 'Offline', 'Warning', 'Critical'],
            datasets: [{
                data: [{{ $stats['online_onus'] }}, {{ $stats['offline_onus'] }}, 0, 0],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#dc2626']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    
    // Traffic Chart
    const trafficCtx = document.getElementById('trafficChart').getContext('2d');
    trafficChart = new Chart(trafficCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'RX Traffic',
                data: [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'TX Traffic',
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
    
    // PON Utilization Chart
    const ponUtilCtx = document.getElementById('ponUtilChart').getContext('2d');
    ponUtilChart = new Chart(ponUtilCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Utilization %',
                data: [],
                backgroundColor: '#8b5cf6'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

// Update OLT status
async function updateOltStatus() {
    try {
        const response = await fetch('/api/dashboard/realtime');
        const data = await response.json();
        
        // Update OLT status grid
        const grid = document.getElementById('oltStatusGrid');
        if (data.olts && data.olts.length > 0) {
            grid.innerHTML = data.olts.map(olt => `
                <div class="border rounded-lg p-4 ${olt.error ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'}">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-semibold text-gray-800">${olt.olt_name}</h4>
                        ${olt.error 
                            ? '<span class="text-red-600 text-xs"><i class="fas fa-exclamation-circle"></i> Error</span>'
                            : '<span class="text-green-600 text-xs"><i class="fas fa-check-circle"></i> Online</span>'
                        }
                    </div>
                    ${olt.error ? `
                        <p class="text-sm text-red-600">${olt.error}</p>
                    ` : `
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">CPU:</span>
                                <span class="font-medium">${olt.cpu_usage || 0}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Temp:</span>
                                <span class="font-medium">${olt.temperature || 0}°C</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">RX:</span>
                                <span class="font-medium">${olt.rx_traffic || '0 B'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">TX:</span>
                                <span class="font-medium">${olt.tx_traffic || '0 B'}</span>
                            </div>
                        </div>
                    `}
                </div>
            `).join('');
        }
        
        // Update ONU Status Chart
        if (data.onu_status && onuStatusChart) {
            onuStatusChart.data.datasets[0].data = [
                data.onu_status.online,
                data.onu_status.offline,
                data.onu_status.warning,
                data.onu_status.critical
            ];
            onuStatusChart.update();
        }
        
        // Update Traffic Chart
        if (data.traffic_trend && trafficChart) {
            trafficChart.data.labels = data.traffic_trend.map(t => t.hour);
            trafficChart.data.datasets[0].data = data.traffic_trend.map(t => t.rx);
            trafficChart.data.datasets[1].data = data.traffic_trend.map(t => t.tx);
            trafficChart.update();
        }
        
    } catch (e) {
        console.error('Failed to update OLT status:', e);
    }
}

// Update chart data
async function updateChartData() {
    try {
        const response = await fetch('/api/dashboard/charts');
        const data = await response.json();
        
        if (data.pon_utilization && ponUtilChart) {
            ponUtilChart.data.labels = data.pon_utilization.map(p => p.name);
            ponUtilChart.data.datasets[0].data = data.pon_utilization.map(p => p.utilization);
            ponUtilChart.update();
        }
    } catch (e) {
        console.error('Failed to update chart data:', e);
    }
}

// Initialize
initCharts();
updateOltStatus();
updateChartData();

// Refresh intervals
setInterval(updateOltStatus, 5000); // Every 5 seconds
setInterval(updateChartData, 30000); // Every 30 seconds
</script>
@endsection
