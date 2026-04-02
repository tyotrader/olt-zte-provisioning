<?php

use Illuminate\Support\Facades\Route;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\OltController;
use App\Controllers\PonController;
use App\Controllers\OnuController;
use App\Controllers\DetectionController;
use App\Controllers\ProvisioningController;
use App\Controllers\OdpController;
use App\Controllers\MapController;
use App\Controllers\ServiceConfigController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // OLT Management
    Route::resource('olt', OltController::class);
    
    // PON Port Monitoring
    Route::get('/pon', [PonController::class, 'index'])->name('pon.index');
    Route::get('/pon/{id}', [PonController::class, 'show'])->name('pon.show');
    
    // ONU Management
    Route::resource('onu', OnuController::class);
    Route::post('/onu/bulk-delete', [OnuController::class, 'bulkDelete'])->name('onu.bulk-delete');
    Route::post('/onu/bulk-reboot', [OnuController::class, 'bulkReboot'])->name('onu.bulk-reboot');
    
    // ONU Detection
    Route::get('/detection', [DetectionController::class, 'index'])->name('detection.index');
    
    // ONU Provisioning
    Route::get('/provisioning', [ProvisioningController::class, 'create'])->name('provisioning.create');
    Route::get('/provisioning/{detection}', [ProvisioningController::class, 'create']);
    Route::post('/provisioning', [ProvisioningController::class, 'store'])->name('provisioning.store');
    Route::get('/provisioning/onu/{id}/configure', [ProvisioningController::class, 'configureOnu'])->name('provisioning.configure');
    
    // ODP Management
    Route::resource('odp', OdpController::class);
    
    // Fiber Map
    Route::get('/map', [MapController::class, 'index'])->name('map.index');
    
    // Service Configuration
    Route::get('/service-config', [ServiceConfigController::class, 'index'])->name('service-config.index');
    
    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    
    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
});

// API Routes
Route::prefix('api')->middleware(['auth'])->group(function () {
    
    // Auth API
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Dashboard API
    Route::get('/dashboard/realtime', [DashboardController::class, 'getRealtimeData']);
    Route::get('/dashboard/charts', [DashboardController::class, 'getChartData']);
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    
    // OLT API
    Route::get('/olts', [OltController::class, 'apiIndex']);
    Route::get('/olt/{id}/test-connection', [OltController::class, 'testConnection']);
    Route::get('/olt/{id}/pon-ports', [OltController::class, 'getPonPorts']);
    Route::get('/olt/{id}/onus', [OltController::class, 'getOnus']);
    Route::get('/olt/{id}/stats', [OltController::class, 'getStats']);
    
    // PON API
    Route::get('/pon/{id}/onus', [PonController::class, 'getOnus']);
    Route::get('/pon/{id}/realtime', [PonController::class, 'getRealtimeStats']);
    Route::get('/olt/{oltId}/pon-ports', [PonController::class, 'getByOlt']);
    
    // ONU API
    Route::get('/onu/{id}/detail', [OnuController::class, 'getDetail']);
    Route::get('/onu/{id}/realtime', [OnuController::class, 'getRealtimeData']);
    Route::post('/onu/{id}/reboot', [OnuController::class, 'reboot']);
    Route::post('/onu/{id}/disable', [OnuController::class, 'disable']);
    Route::post('/onu/{id}/enable', [OnuController::class, 'enable']);
    Route::post('/onu/{id}/factory-reset', [OnuController::class, 'factoryReset']);
    Route::post('/onu/bulk-delete', [OnuController::class, 'bulkDelete']);
    Route::post('/onu/bulk-reboot', [OnuController::class, 'bulkReboot']);
    Route::get('/olt/{oltId}/pon/{slot}/{port}/onus', [OnuController::class, 'getByOltAndPon']);
    
    // Detection API
    Route::post('/detection/scan', [DetectionController::class, 'scan']);
    Route::get('/detection/pending', [DetectionController::class, 'getPending']);
    Route::get('/detection/stats', [DetectionController::class, 'getStats']);
    Route::post('/detection/{id}/ignore', [DetectionController::class, 'ignore']);
    Route::post('/detection/{id}/unignore', [DetectionController::class, 'unignore']);
    Route::delete('/detection/{id}', [DetectionController::class, 'destroy']);
    
    // Provisioning API
    Route::get('/provisioning/next-onu-id', [ProvisioningController::class, 'getNextOnuId']);
    Route::post('/provisioning/auto', [ProvisioningController::class, 'autoProvision']);
    Route::post('/provisioning/onu/{id}/configure', [ProvisioningController::class, 'saveConfiguration']);
    
    // ODP API
    Route::get('/odps', [OdpController::class, 'apiIndex']);
    Route::get('/olt/{oltId}/odps', [OdpController::class, 'getByOlt']);
    Route::get('/odp/{id}/available-port', [OdpController::class, 'getAvailablePort']);
    
    // Map API
    Route::get('/map/data', [MapController::class, 'getData']);
    Route::get('/map/olt', [MapController::class, 'getOltMarkers']);
    Route::get('/map/odp', [MapController::class, 'getOdpMarkers']);
    Route::get('/map/onu', [MapController::class, 'getOnuMarkers']);
    Route::get('/map/search', [MapController::class, 'search']);
    Route::post('/map/import', [MapController::class, 'import']);
    Route::get('/map/export', [MapController::class, 'export']);
});
