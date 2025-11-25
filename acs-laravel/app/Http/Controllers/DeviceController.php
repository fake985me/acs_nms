<?php

namespace App\Http\Controllers;

use App\Services\AcsApiService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    protected $acsApi;

    public function __construct(AcsApiService $acsApi)
    {
        $this->acsApi = $acsApi;
    }

    /**
     * Display a listing of devices
     */
    public function index(Request $request)
    {
        $filters = $request->only(['manufacturer', 'oui', 'ip', 'tags']);
        $devices = $this->acsApi->getDevices($filters);

        return view('devices.index', compact('devices', 'filters'));
    }

    /**
     * Display the specified device
     */
    public function show($deviceId)
    {
        $device = $this->acsApi->getDevice($deviceId);
        
        if (!$device) {
            return redirect()->route('devices.index')->with('error', 'Device not found');
        }

        $parameters = $this->acsApi->getParameters($deviceId);
        $tasks = $this->acsApi->getTasks($deviceId);
        
        // Get latest signal metrics
        $latestSignal = \App\Models\SignalMetric::where('device_id', $deviceId)
            ->latest()
            ->first();
        
        // Get signal history (last 24 hours)
        $signalHistory = \App\Models\SignalMetric::where('device_id', $deviceId)
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at')
            ->get();

        // Organize parameters in tree structure
        $parameterTree = $this->buildParameterTree($parameters);

        return view('devices.show', compact('device', 'parameters', 'parameterTree', 'tasks', 'latestSignal', 'signalHistory'));
    }

    /**
     * Build parameter tree from flat list
     */
    protected function buildParameterTree($parameters)
    {
        $tree = [];
        
        foreach ($parameters as $param) {
            $parts = explode('.', $param['name']);
            $current = &$tree;
            
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
            
            $current['_value'] = $param['value'];
            $current['_type'] = $param['type'];
        }
        
        return $tree;
    }

    /**
     * Lock device
     */
    public function lock($deviceId)
    {
        $success = $this->acsApi->lockDevice($deviceId);
        
        if ($success) {
            return back()->with('success', 'Device locked successfully');
        }
        
        return back()->with('error', 'Failed to lock device');
    }

    /**
     * Unlock device
     */
    public function unlock($deviceId)
    {
        $success = $this->acsApi->unlockDevice($deviceId);
        
        if ($success) {
            return back()->with('success', 'Device unlocked successfully');
        }
        
        return back()->with('error', 'Failed to unlock device');
    }

    /**
     * Delete device
     */
    public function destroy($deviceId)
    {
        $success = $this->acsApi->deleteDevice($deviceId);
        
        if ($success) {
            return redirect()->route('devices.index')->with('success', 'Device deleted successfully');
        }
        
        return back()->with('error', 'Failed to delete device');
    }
}
