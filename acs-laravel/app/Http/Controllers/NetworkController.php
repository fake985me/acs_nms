<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Services\AcsApiService;
use Illuminate\Http\Request;

class NetworkController extends Controller
{
    protected $acsApi;

    public function __construct(AcsApiService $acsApi)
    {
        $this->acsApi = $acsApi;
    }

    public function topology(Request $request)
    {
        $filter = $request->get('filter', 'all'); // all, ftth, fttb
        
        return view('network.topology', compact('filter'));
    }

    public function getTopologyData(Request $request)
    {
        $filter = $request->get('filter', 'all');
        
        // Get OLTs and devices
        $olts = Olt::where('is_active', true)->get();
        $devices = $this->acsApi->getDevices();
        
        $nodes = [];
        $edges = [];
        
        // Add OLT nodes (level 0)
        foreach ($olts as $olt) {
            $nodes[] = [
                'id' => 'olt-' . $olt->id,
                'label' => $olt->name,
                'level' => 0,
                'group' => 'olt',
                'title' => "OLT: {$olt->name}\nIP: {$olt->ip_address}",
            ];
            
            // Simulate PON ports (level 1)
            for ($i = 1; $i <= 4; $i++) {
                $ponId = "pon-{$olt->id}-{$i}";
                $nodes[] = [
                    'id' => $ponId,
                    'label' => "PON 0/{$i}",
                    'level' => 1,
                    'group' => 'pon',
                    'title' => "PON Port 0/{$i}",
                ];
                
                $edges[] = [
                    'from' => 'olt-' . $olt->id,
                    'to' => $ponId,
                ];
            }
        }
        
        // Add ONT nodes (level 2)
        $ontCount = 0;
        foreach ($devices as $index => $device) {
            if ($ontCount >= 20) break; // Limit to 20 ONTs for performance
            
            // Assign to random PON port
            $oltIndex = $index % count($olts);
            $ponIndex = ($index % 4) + 1;
            $ponId = "pon-{$olts[$oltIndex]->id}-{$ponIndex}";
            
            $isOnline = isset($device['last_inform_at']) && 
                        strtotime($device['last_inform_at']) > strtotime('-5 minutes');
            
            $nodes[] = [
                'id' => 'ont-' . $device['id'],
                'label' => $device['manufacturer'] ?? 'ONT',
                'level' => 2,
                'group' => $isOnline ? 'ont-online' : 'ont-offline',
                'title' => "Serial: {$device['id']}\nStatus: " . ($isOnline ? 'Online' : 'Offline'),
            ];
            
            $edges[] = [
                'from' => $ponId,
                'to' => 'ont-' . $device['id'],
            ];
            
            $ontCount++;
        }
        
        return response()->json([
            'nodes' => $nodes,
            'edges' => $edges,
        ]);
    }
}
