<?php

namespace App\Http\Controllers;

use App\Services\AcsApiService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    protected $acsApi;

    public function __construct(AcsApiService $acsApi)
    {
        $this->acsApi = $acsApi;
    }

    /**
     * Create reboot task
     */
    public function reboot(Request $request, $deviceId)
    {
        $task = $this->acsApi->createRebootTask($deviceId);
        
        if ($task) {
            return back()->with('success', 'Reboot task created successfully');
        }
        
        return back()->with('error', 'Failed to create reboot task');
    }

    /**
     * Create set parameters task
     */
    public function setParameters(Request $request, $deviceId)
    {
        $validated = $request->validate([
            'parameters' => 'required|array',
            'parameters.*' => 'required'
        ]);

        $task = $this->acsApi->createSetParametersTask($deviceId, $validated['parameters']);
        
        if ($task) {
            return back()->with('success', 'SetParameterValues task created successfully');
        }
        
        return back()->with('error', 'Failed to create task');
    }

    /**
     * Create get parameters task
     */
    public function getParameters(Request $request, $deviceId)
    {
        $parameterNames = $request->input('parameter_names', ['InternetGatewayDevice.']);
        
        $task = $this->acsApi->createGetParametersTask($deviceId, $parameterNames);
        
        if ($task) {
            return back()->with('success', 'GetParameterValues task created successfully');
        }
        
        return back()->with('error', 'Failed to create task');
    }

    /**
     * Create download task (firmware)
     */
    public function download(Request $request, $deviceId)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'file_type' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'target_filename' => 'nullable|string',
        ]);

        $task = $this->acsApi->createDownloadTask($deviceId, $validated);
        
        if ($task) {
            return back()->with('success', 'Download task created successfully');
        }
        
        return back()->with('error', 'Failed to create download task');
    }

    /**
     * Create factory reset task
     */
    public function factoryReset(Request $request, $deviceId)
    {
        // Require confirmation
        if (!$request->input('confirm')) {
            return back()->with('error', 'Confirmation required for factory reset');
        }

        $task = $this->acsApi->createFactoryResetTask($deviceId);
        
        if ($task) {
            return back()->with('success', 'Factory reset task created successfully');
        }
        
        return back()->with('error', 'Failed to create factory reset task');
    }
}
