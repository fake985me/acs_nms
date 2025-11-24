<?php

namespace App\Http\Controllers;

use App\Services\AcsApiService;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    protected $acsApi;

    public function __construct(AcsApiService $acsApi)
    {
        $this->acsApi = $acsApi;
    }

    public function index()
    {
        $stats = $this->acsApi->getStatistics();
        $devices = $this->acsApi->getDevices();
        $health = $this->acsApi->getHealth();

        // Calculate additional metrics
        $metrics = [
            'total_devices' => count($devices),
            'online_count' => $stats['online'] ?? 0,
            'offline_count' => $stats['offline'] ?? 0,
            'online_percentage' => count($devices) > 0 ? round(($stats['online'] ?? 0) / count($devices) * 100, 1) : 0,
        ];

        // Manufacturer breakdown
        $byManufacturer = collect($devices)
            ->groupBy('manufacturer')
            ->map(fn($group) => $group->count())
            ->toArray();

        return view('monitoring.index', compact('stats', 'devices', 'health', 'metrics', 'byManufacturer'));
    }

    public function metrics()
    {
        // API endpoint for real-time metrics
        $stats = $this->acsApi->getStatistics();
        $health = $this->acsApi->getHealth();
        
        return response()->json([
            'stats' => $stats,
            'health' => $health,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
