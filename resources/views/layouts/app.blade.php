<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ZTE OLT Provisioning System')</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Socket.IO -->
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    
    <style>
        .sidebar { transition: all 0.3s; }
        .sidebar-collapsed { width: 64px; }
        .sidebar-expanded { width: 260px; }
        .nav-item:hover { background-color: rgba(59, 130, 246, 0.1); }
        .nav-item.active { background-color: rgba(59, 130, 246, 0.2); border-right: 3px solid #3b82f6; }
        .status-online { color: #10b981; }
        .status-offline { color: #ef4444; }
        .status-warning { color: #f59e0b; }
        .rx-bar { height: 8px; border-radius: 4px; background: linear-gradient(90deg, #ef4444 0%, #f59e0b 50%, #10b981 100%); position: relative; }
        .rx-indicator { position: absolute; width: 4px; height: 12px; background: #1f2937; top: -2px; transform: translateX(-50%); }
    </style>
    
    @yield('styles')
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('layouts.sidebar')
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-6">
                <div class="flex items-center">
                    <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 mr-4">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800">@yield('page_title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div id="connectionStatus" class="flex items-center text-sm">
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                        <span class="text-gray-600">Connected</span>
                    </div>
                    <div class="relative">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                            <img src="https://ui-avatars.com/api/?name={{ auth()->user()->fullname ?? 'Admin' }}&background=3b82f6&color=fff" 
                                 class="w-8 h-8 rounded-full" alt="Profile">
                            <span class="hidden md:block">{{ auth()->user()->fullname ?? 'Administrator' }}</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
                @if(session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                        <p>{{ session('error') }}</p>
                    </div>
                @endif
                
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Global JavaScript -->
    <script>
        const API_BASE_URL = '{{ url('/api') }}';
        const WS_URL = '{{ env('WS_URL', 'ws://localhost:6001') }}';
        
        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');
        });
        
        // Token management
        function getToken() {
            return localStorage.getItem('token');
        }
        
        function setToken(token) {
            localStorage.setItem('token', token);
        }
        
        function removeToken() {
            localStorage.removeItem('token');
        }
        
        // API Helper
        async function apiRequest(url, options = {}) {
            const token = getToken();
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers
            };
            
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
            
            const response = await fetch(url, {
                ...options,
                headers
            });
            
            if (response.status === 401) {
                removeToken();
                window.location.href = '/login';
                return;
            }
            
            return response.json();
        }
        
        // Format helpers
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        
        function formatRxPower(power) {
            if (power === null || power === undefined) return '-';
            const num = parseFloat(power);
            if (num >= -25) return `<span class="text-green-600">${num} dBm</span>`;
            if (num >= -27) return `<span class="text-yellow-600">${num} dBm</span>`;
            return `<span class="text-red-600">${num} dBm</span>`;
        }
        
        function getStatusBadge(status) {
            const classes = {
                'online': 'bg-green-100 text-green-800',
                'offline': 'bg-red-100 text-red-800',
                'detected': 'bg-blue-100 text-blue-800',
                'registered': 'bg-purple-100 text-purple-800',
                'warning': 'bg-yellow-100 text-yellow-800'
            };
            return `<span class="px-2 py-1 rounded-full text-xs font-medium ${classes[status] || 'bg-gray-100'}">${status}</span>`;
        }
    </script>
    
    @yield('scripts')
</body>
</html>
