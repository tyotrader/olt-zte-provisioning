<aside id="sidebar" class="sidebar sidebar-expanded bg-gray-900 text-white flex flex-col h-full overflow-y-auto">
    <div class="p-4 flex items-center justify-center border-b border-gray-800">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-network-wired text-white"></i>
            </div>
            <span class="text-lg font-bold sidebar-text">Smart OLT</span>
        </div>
    </div>
    
    <nav class="flex-1 py-4">
        <ul class="space-y-1">
            <li>
                <a href="{{ route('dashboard') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">Dashboard</span>
                </a>
            </li>
            
            <li class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider sidebar-text">
                Management
            </li>
            
            <li>
                <a href="{{ route('olt.index') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('olt.*') ? 'active' : '' }}">
                    <i class="fas fa-server w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">OLT Management</span>
                </a>
            </li>
            
            <li>
                <a href="{{ route('pon.index') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('pon.*') ? 'active' : '' }}">
                    <i class="fas fa-ethernet w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">PON Monitoring</span>
                </a>
            </li>
            
            <li>
                <a href="{{ route('detection.index') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('detection.*') ? 'active' : '' }}">
                    <i class="fas fa-search w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">ONU Detection</span>
                    <span id="detectionCount" class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-0.5 hidden">0</span>
                </a>
            </li>
            
            <li>
                <a href="{{ route('onu.index') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('onu.*') ? 'active' : '' }}">
                    <i class="fas fa-plug w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">ONU Management</span>
                </a>
            </li>
            
            <li>
                <a href="{{ route('odp.index') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('odp.*') ? 'active' : '' }}">
                    <i class="fas fa-box w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">ODP Management</span>
                </a>
            </li>
            
            <li class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider sidebar-text">
                Configuration
            </li>
            
            <li>
                <a href="{{ route('service-config.index') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('service-config.*') ? 'active' : '' }}">
                    <i class="fas fa-cogs w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">Service Config</span>
                </a>
            </li>
            
            <li>
                <a href="{{ route('map.index') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('map.*') ? 'active' : '' }}">
                    <i class="fas fa-map-marked-alt w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">Fiber Map</span>
                </a>
            </li>
            
            <li class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider sidebar-text">
                System
            </li>
            
            <li>
                <a href="{{ route('logs.index') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                    <i class="fas fa-history w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">Logs</span>
                </a>
            </li>
            
            <li>
                <a href="{{ route('settings') }}" class="nav-item flex items-center px-4 py-3 text-gray-300 hover:text-white {{ request()->routeIs('settings') ? 'active' : '' }}">
                    <i class="fas fa-cog w-6 text-center"></i>
                    <span class="ml-3 sidebar-text">Settings</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="p-4 border-t border-gray-800">
        <a href="{{ route('logout') }}" class="flex items-center px-4 py-3 text-gray-300 hover:text-red-400">
            <i class="fas fa-sign-out-alt w-6 text-center"></i>
            <span class="ml-3 sidebar-text">Logout</span>
        </a>
    </div>
</aside>

<script>
// Update detection count badge
async function updateDetectionCount() {
    try {
        const response = await fetch('/api/detection/stats');
        const data = await response.json();
        const badge = document.getElementById('detectionCount');
        if (badge && data.pending > 0) {
            badge.textContent = data.pending;
            badge.classList.remove('hidden');
        } else if (badge) {
            badge.classList.add('hidden');
        }
    } catch (e) {
        console.error('Failed to fetch detection count');
    }
}

updateDetectionCount();
setInterval(updateDetectionCount, 60000); // Update every minute
</script>
