<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class SnmpService
{
    protected $session;

    /**
     * Check if SNMP extension is available
     */
    public function isAvailable(): bool
    {
        return extension_loaded('snmp');
    }

    /**
     * Connect to SNMP device (supports v2c and v3)
     */
    public function connect($host, $config)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $version = $config['version'] ?? 'v2c';
        
        try {
            if ($version === 'v3' || $version === '3') {
                // SNMPv3 connection
                $secLevel = $config['sec_level'] ?? 'authPriv';
                $authProtocol = strtoupper($config['auth_protocol'] ?? 'SHA');
                $authPassphrase = $config['auth_passphrase'] ?? '';
                $privProtocol = strtoupper($config['priv_protocol'] ?? 'AES');
                $privPassphrase = $config['priv_passphrase'] ?? '';
                $secName = $config['sec_name'] ?? 'admin';
                
                // Build SNMPv3 session
                $this->session = new \SNMP(\SNMP::VERSION_3, $host, $secName);
                
                // Set security level
                if ($secLevel === 'authPriv') {
                    $this->session->setSecurity(
                        'authPriv',
                        $authProtocol,
                        $authPassphrase,
                        $privProtocol,
                        $privPassphrase
                    );
                } elseif ($secLevel === 'authNoPriv') {
                    $this->session->setSecurity(
                        'authNoPriv',
                        $authProtocol,
                        $authPassphrase
                    );
                } else { // noAuthNoPriv
                    $this->session->setSecurity('noAuthNoPriv');
                }
                
                return true;
                
            } else {
                // SNMPv2c connection
                $community = $config['community'] ?? 'public';
                $this->session = new \SNMP(\SNMP::VERSION_2C, $host, $community);
                return true;
            }
        } catch (\Exception $e) {
            Log::error("SNMP connection failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test SNMP connectivity to OLT
     */
    public function testConnection($host, $community = 'public', $version = '2c', $port = 161, $timeout = 6000): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'SNMP extension not installed'
            ];
        }

        try {
            $timeoutSec = $timeout / 1000;
            
            if ($version === '2c' || $version === '2') {
                $result = @snmp2_get($host . ':' . $port, $community, '1.3.6.1.2.1.1.1.0', $timeoutSec * 1000000);
                
                if ($result === false) {
                    return [
                        'success' => false,
                        'message' => 'Failed to connect via SNMP v2c'
                    ];
                }

                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'system_description' => $result
                ];
            }

            return [
                'success' => false,
                'message' => 'SNMP v3 test connection - use connect() method instead'
            ];

        } catch (Exception $e) {
            Log::error('SNMP connection test failed', [
                'host' => $host,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get OID value via SNMP
     */
    public function get($host, $community, $oid, $version = '2c', $port = 161, $timeout = 6000)
    {
        if (!$this->isAvailable()) {
            throw new Exception('SNMP extension not available');
        }

        try {
            $timeoutSec = $timeout / 1000;

            if ($version === '2c' || $version === '2') {
                return @snmp2_get($host . ':' . $port, $community, $oid, $timeoutSec * 1000000);
            }

            throw new Exception('Use connect() for SNMP v3');
        } catch (Exception $e) {
            Log::error('SNMP GET failed', [
                'host' => $host,
                'oid' => $oid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Walk OID tree via SNMP
     */
    public function walk($host, $community, $oid, $version = '2c', $port = 161, $timeout = 6000): array
    {
        if (!$this->isAvailable()) {
            throw new Exception('SNMP extension not available');
        }

        try {
            $timeoutSec = $timeout / 1000;

            if ($version === '2c' || $version === '2') {
                $result = @snmp2_real_walk($host . ':' . $port, $community, $oid, $timeoutSec * 1000000);
                return $result ?: [];
            }

            throw new Exception('Use connect() for SNMP v3');
        } catch (Exception $e) {
            Log::error('SNMP WALK failed', [
                'host' => $host,
                'oid' => $oid,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * DASAN-specific OID mappings for GPON monitoring
     */
    protected function getDasanOids(): array
    {
        return [
            'system' => [
                'name' => '1.3.6.1.2.1.1.5.0',
                'description' => '1.3.6.1.2.1.1.1.0',
                'uptime' => '1.3.6.1.2.1.1.3.0',
            ],
            'pon' => [
                'status' => '1.3.6.1.4.1.6296.9.1.1.1.21.1.3',
                'oper_status' => '1.3.6.1.4.1.6296.9.1.1.1.21.1.4',
                'ont_count' => '1.3.6.1.4.1.6296.9.1.1.1.21.1.7',
            ],
            'ont' => [
                'serial' => '1.3.6.1.4.1.6296.9.1.1.1.22.1.3',
                'status' => '1.3.6.1.4.1.6296.9.1.1.1.22.1.4',
                'distance' => '1.3.6.1.4.1.6296.9.1.1.1.22.1.6',
                'rx_power' => '1.3.6.1.4.1.6296.9.1.1.1.22.1.10',
                'tx_power' => '1.3.6.1.4.1.6296.9.1.1.1.22.1.11',
                'olt_rx_power' => '1.3.6.1.4.1.6296.9.1.1.1.22.1.12',
                'temperature' => '1.3.6.1.4.1.6296.9.1.1.1.22.1.13',
                'voltage' => '1.3.6.1.4.1.6296.9.1.1.1.22.1.14',
            ],
        ];
    }

    /**
     * Get ONT details from DASAN OLT
     */
    public function getDasanOntInfo($host, $community, $ponPort, $ontId, $version = '2c', $port = 161)
    {
        $oids = $this->getDasanOids();
        $ontIndex = "{$ponPort}.{$ontId}";
        
        try {
            return [
                'serial' => $this->get($host, $community, $oids['ont']['serial'] . '.' . $ontIndex, $version, $port),
                'status' => $this->get($host, $community, $oids['ont']['status'] . '.' . $ontIndex, $version, $port),
                'rx_power' => $this->get($host, $community, $oids['ont']['rx_power'] . '.' . $ontIndex, $version, $port),
                'tx_power' => $this->get($host, $community, $oids['ont']['tx_power'] . '.' . $ontIndex, $version, $port),
                'temperature' => $this->get($host, $community, $oids['ont']['temperature'] . '.' . $ontIndex, $version, $port),
                'voltage' => $this->get($host, $community, $oids['ont']['voltage'] . '.' . $ontIndex, $version, $port),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get DASAN ONT info: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all ONTs on a PON port
     */
    public function getDasanPonOnts($host, $community, $ponPort, $version = '2c', $port = 161): array
    {
        $oids = $this->getDasanOids();
        
        try {
            $ontSerials = $this->walk($host, $community, $oids['ont']['serial'] . '.' . $ponPort, $version, $port);
            $onts = [];
            
            foreach ($ontSerials as $index => $serial) {
                $parts = explode('.', $index);
                $ontId = end($parts);
                
                $onts[] = [
                    'ont_id' => $ontId,
                    'serial' => $serial,
                    'info' => $this->getDasanOntInfo($host, $community, $ponPort, $ontId, $version, $port),
                ];
            }
            
            return $onts;
        } catch (\Exception $e) {
            Log::error("Failed to get DASAN PON ONTs: " . $e->getMessage());
            return [];
        }
    }
}
