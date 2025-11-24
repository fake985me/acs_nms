<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AcsApiService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = env('ACS_API_URL', 'http://localhost:7547/api');
        $this->timeout = 30;
    }

    /**
     * Get all devices
     */
    public function getDevices(array $filters = [])
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices", $filters);

            if ($response->successful()) {
                return $response->json('data', []);
            }

            Log::error('ACS API Error: Failed to get devices', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single device
     */
    public function getDevice($deviceId)
    {
        try {
            $encodedDeviceId = rawurlencode($deviceId);
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices/{$encodedDeviceId}");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get device parameters
     */
    public function getParameters($deviceId, $nameFilter = null)
    {
        try {
            $encodedDeviceId = rawurlencode($deviceId);
            $query = $nameFilter ? ['name' => $nameFilter] : [];
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices/{$encodedDeviceId}/parameters", $query);

            if ($response->successful()) {
                return $response->json('data', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get device tasks
     */
    public function getTasks($deviceId, $status = null)
    {
        try {
            $encodedDeviceId = rawurlencode($deviceId);
            $query = $status ? ['status' => $status] : [];
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices/{$encodedDeviceId}/tasks", $query);

            if ($response->successful()) {
                return $response->json('data', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create reboot task
     */
    public function createRebootTask($deviceId)
    {
        try {
            $encodedDeviceId = rawurlencode($deviceId);
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedDeviceId}/tasks/reboot");

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('ACS API Error: Failed to create reboot task', [
                'device_id' => $deviceId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create SetParameterValues task
     */
    public function createSetParametersTask($deviceId, array $parameters)
    {
        try {
            $encodedDeviceId = rawurlencode($deviceId);
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedDeviceId}/tasks/set-parameters", [
                    'parameters' => $parameters
                ]);

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create GetParameterValues task
     */
    public function createGetParametersTask($deviceId, array $parameterNames = [])
    {
        try {
            $encodedDeviceId = rawurlencode($deviceId);
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedDeviceId}/tasks/get-parameters", [
                    'parameterNames' => $parameterNames
                ]);

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create Download task (firmware)
     */
    public function createDownloadTask($deviceId, array $options)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$deviceId}/tasks/download", $options);

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create FactoryReset task
     */
    public function createFactoryResetTask($deviceId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$deviceId}/tasks/factory-reset");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lock device
     */
    public function lockDevice($deviceId)
    {
        try {
            $encodedDeviceId = rawurlencode($deviceId);
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedDeviceId}/lock");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Unlock device
     */
    public function unlockDevice($deviceId)
    {
        try {
            $encodedDeviceId = rawurlencode($deviceId);
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedDeviceId}/unlock");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ACS API Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get ACS health status
     */
    public function getHealth()
    {
        try {
            $response = Http::timeout(5)->get(str_replace('/api', '/health', $this->baseUrl));

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get device statistics
     */
    public function getStatistics()
    {
        $devices = $this->getDevices();
        
        $now = now();
        $online = collect($devices)->filter(function ($device) use ($now) {
            if (!isset($device['last_inform_at'])) return false;
            $lastInform = \Carbon\Carbon::parse($device['last_inform_at']);
            return $lastInform->diffInMinutes($now) <= 5;
        })->count();

        return [
            'total' => count($devices),
            'online' => $online,
            'offline' => count($devices) - $online,
            'manufacturers' => collect($devices)->pluck('manufacturer')->unique()->count(),
        ];
    }
}
