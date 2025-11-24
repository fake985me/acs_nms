<?php

namespace App\Console\Commands;

use App\Models\Olt;
use App\Models\OntOltAssignment;
use App\Models\SignalMetric;
use App\Services\AcsApiService;
use App\Services\SnmpService;
use Illuminate\Console\Command;

class CollectSignalMetrics extends Command
{
    protected $signature = 'signal:collect';
    protected $description = 'Collect signal quality metrics from OLTs via SNMP';

    protected $snmp;
    protected $acsApi;

    public function __construct(SnmpService $snmp, AcsApiService $acsApi)
    {
        parent::__construct();
        $this->snmp = $snmp;
        $this->acsApi = $acsApi;
    }

    public function handle()
    {
        $this->info('Starting signal metrics collection...');
        
        $olts = Olt::where('is_active', true)->get();
        $totalMetrics = 0;
        
        foreach ($olts as $olt) {
            $this->info("Processing OLT: {$olt->name}");
            
            // Get ONT assignments for this OLT
            $assignments = OntOltAssignment::where('olt_id', $olt->id)->get();
            
            foreach ($assignments as $assignment) {
                try {
                    // Simulate SNMP query for signal quality
                    // In production, use actual SNMP OIDs for optical power
                    $rxPower = $this->getSimulatedRxPower();
                    $txPower = $this->getSimulatedTxPower();
                    $temperature = rand(20, 50);
                    $voltage = rand(32, 34) / 10;
                    
                    // Store metric
                    SignalMetric::create([
                        'device_id' => $assignment->device_id,
                        'olt_id' => $olt->id,
                        'pon_port' => $assignment->pon_port,
                        'ont_id' => $assignment->ont_id,
                        'rx_power' => $rxPower,
                        'tx_power' => $txPower,
                        'temperature' => $temperature,
                        'voltage' => $voltage,
                    ]);
                    
                    $totalMetrics++;
                    
                } catch (\Exception $e) {
                    $this->error("Failed to collect metrics for device {$assignment->device_id}: " . $e->getMessage());
                }
            }
        }
        
        $this->info("Signal metrics collection complete. Collected {$totalMetrics} metrics.");
        
        return 0;
    }

    private function getSimulatedRxPower()
    {
        // Simulate RX power between -25 dBm (good) and -8 dBm (excellent)
        // With some occasional bad values
        $random = rand(0, 100);
        
        if ($random > 90) {
            // 10% chance of weak signal
            return rand(-35, -26) / 1.0; // -35 to -26 dBm (weak)
        } else if ($random > 70) {
            // 20% chance of marginal signal
            return rand(-26, -24) / 1.0; // -26 to -24 dBm (marginal)
        } else {
            // 70% chance of good signal
            return rand(-24, -8) / 1.0; // -24 to -8 dBm (good)
        }
    }

    private function getSimulatedTxPower()
    {
        // Simulate TX power between 0 and 4 dBm
        return rand(0, 40) / 10.0;
    }
}
