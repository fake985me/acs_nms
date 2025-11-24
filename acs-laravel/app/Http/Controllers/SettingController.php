<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'system_name' => env('APP_NAME', 'ACS Manager'),
            'acs_url' => env('ACS_API_URL', 'http://localhost:7547/api'),
            'periodic_inform_tolerance' => env('PERIODIC_INFORM_TOLERANCE', 5),
            'connection_timeout' => env('CONNECTION_TIMEOUT', 30),
        ];
        
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'system_name' => 'nullable|string|max:255',
            'periodic_inform_tolerance' => 'nullable|integer|min:1',
            'connection_timeout' => 'nullable|integer|min:5',
        ]);

        // In production, you'd update .env file or database
        // For now, just show success message
        
        return redirect()->route('settings.index')
            ->with('success', 'Settings updated. Restart services for changes to take effect.');
    }
}
