<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Devices
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('/devices/{deviceId}', [DeviceController::class, 'show'])->name('devices.show');
    Route::post('/devices/{deviceId}/lock', [DeviceController::class, 'lock'])->name('devices.lock');
    Route::post('/devices/{deviceId}/unlock', [DeviceController::class, 'unlock'])->name('devices.unlock');
    
    // Tasks
    Route::post('/devices/{deviceId}/tasks/reboot', [TaskController::class, 'reboot'])->name('tasks.reboot');
    Route::post('/devices/{deviceId}/tasks/set-parameters', [TaskController::class, 'setParameters'])->name('tasks.setParameters');
    Route::post('/devices/{deviceId}/tasks/get-parameters', [TaskController::class, 'getParameters'])->name('tasks.getParameters');
    Route::post('/devices/{deviceId}/tasks/download', [TaskController::class, 'download'])->name('tasks.download');
    Route::post('/devices/{deviceId}/tasks/factory-reset', [TaskController::class, 'factoryReset'])->name('tasks.factoryReset');
    
    // OLT Management
    Route::resource('olts', App\Http\Controllers\OltController::class);
    Route::post('/olts/{olt}/test-connection', [App\Http\Controllers\OltController::class, 'testConnection'])->name('olts.testConnection');
    
    // Presets
    Route::resource('presets', App\Http\Controllers\PresetController::class);
    Route::post('/presets/{preset}/apply/{deviceId}', [App\Http\Controllers\PresetController::class, 'apply'])->name('presets.apply');
    
    // Firmware
    Route::resource('firmware', App\Http\Controllers\FirmwareController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::post('/firmware/{firmware}/deploy/{deviceId}', [App\Http\Controllers\FirmwareController::class, 'deploy'])->name('firmware.deploy');
    
    // Provisions
    Route::resource('provisions', App\Http\Controllers\ProvisionController::class);
    Route::post('/provisions/{provision}/execute', [App\Http\Controllers\ProvisionController::class, 'execute'])->name('provisions.execute');
    
    // Monitoring
    Route::get('/monitoring', [App\Http\Controllers\MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/metrics', [App\Http\Controllers\MonitoringController::class, 'metrics'])->name('monitoring.metrics');
    
    // Settings
    Route::get('/settings', [App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [App\Http\Controllers\SettingController::class, 'update'])
        ->middleware('role:super_admin,admin')
        ->name('settings.update');
    
    // Network Topology
    Route::get('/network/topology', [App\Http\Controllers\NetworkController::class, 'topology'])->name('network.topology');
    Route::get('/network/topology-data', [App\Http\Controllers\NetworkController::class, 'getTopologyData'])->name('network.topologyData');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

