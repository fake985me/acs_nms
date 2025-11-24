<?php

namespace App\Http\Controllers;

use App\Services\AcsApiService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $acsApi;

    public function __construct(AcsApiService $acsApi)
    {
        $this->acsApi = $acsApi;
    }

    public function index(Request $request)
    {
        $stats = $this->acsApi->getStatistics();
        $devices = $this->acsApi->getDevices();
        $health = $this->acsApi->getHealth();

        // Get filter parameters
        $filterManufacturer = $request->get('manufacturer');
        $filterStatus = $request->get('status');
        $filterModel = $request->get('model');

        // Apply filters
        $filteredDevices = collect($devices);
        
        if ($filterManufacturer) {
            $filteredDevices = $filteredDevices->filter(function($device) use ($filterManufacturer) {
                return isset($device['manufacturer']) && $device['manufacturer'] === $filterManufacturer;
            });
        }
        
        if ($filterStatus) {
            $filteredDevices = $filteredDevices->filter(function($device) use ($filterStatus) {
                $isOnline = isset($device['last_inform_at']) && 
                    \Carbon\Carbon::parse($device['last_inform_at'])->diffInMinutes(now()) <= 5;
                return ($filterStatus === 'online' && $isOnline) || ($filterStatus === 'offline' && !$isOnline);
            });
        }
        
        if ($filterModel) {
            $filteredDevices = $filteredDevices->filter(function($device) use ($filterModel) {
                return isset($device['model_name']) && $device['model_name'] === $filterModel;
            });
        }

        // Get recent devices from filtered set
        $recentDevices = $filteredDevices
            ->sortByDesc('last_inform_at')
            ->take(10)
            ->values()
            ->toArray();

        // Calculate manufacturer distribution
        $byManufacturer = collect($devices)
            ->groupBy('manufacturer')
            ->map(fn($group) => $group->count())
            ->toArray();

        $stats['by_manufacturer'] = $byManufacturer;

        // Get network activity data (last 24 hours, hourly)
        $networkActivity = $this->getNetworkActivity($devices);

        // Get unique manufacturers and models for filter dropdowns
        $manufacturers = collect($devices)
            ->pluck('manufacturer')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
            
        $models = collect($devices)
            ->pluck('model_name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return view('dashboard', compact(
            'stats', 
            'recentDevices', 
            'health', 
            'networkActivity',
            'manufacturers',
            'models',
            'filterManufacturer',
            'filterStatus',
            'filterModel'
        ));
    }

    /**
     * Get network activity statistics for the last 24 hours
     */
    private function getNetworkActivity($devices)
    {
        $hours = [];
        $onlineCounts = [];
        $offlineCounts = [];
        
        // Generate hourly data for last 24 hours
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i);
            $hours[] = $hour->format('H:00');
            
            // Count devices that were online at this hour
            $online = collect($devices)->filter(function($device) use ($hour) {
                if (!isset($device['last_inform_at'])) return false;
                $lastInform = \Carbon\Carbon::parse($device['last_inform_at']);
                // Device is considered online if last_inform was within 5 minutes of the hour
                return $lastInform->diffInMinutes($hour) <= 5 && $lastInform->lte($hour);
            })->count();
            
            $total = count($devices);
            $onlineCounts[] = $online;
            $offlineCounts[] = max(0, $total - $online);
        }
        
        return [
            'labels' => $hours,
            'online' => $onlineCounts,
            'offline' => $offlineCounts,
        ];
    }
}
