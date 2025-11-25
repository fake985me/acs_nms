<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DatabaseService
{
    protected $dbPath;
    protected $pdo;

    public function __construct()
    {
        $this->dbPath = base_path('../acs.db');
    }

    /**
     * Get PDO connection to ACS database
     */
    protected function getConnection()
    {
        if (!$this->pdo) {
            try {
                $this->pdo = new \PDO("sqlite:{$this->dbPath}");
                $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\Exception $e) {
                Log::error('Database connection error: ' . $e->getMessage());
                throw $e;
            }
        }
        return $this->pdo;
    }

    /**
     * Get statistics for all tables
     */
    public function getTableStats()
    {
        $tables = [
            'devices',
            'device_parameters',
            'tasks',
            'device_sessions',
            'presets',
            'preset_parameters',
            'provisions',
            'virtual_parameters',
            'device_files',
            'device_faults',
            'provision_logs'
        ];

        $stats = [];
        $db = $this->getConnection();

        foreach ($tables as $table) {
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM {$table}");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $stats[$table] = [
                    'count' => $result['count'],
                    'exists' => true
                ];
            } catch (\Exception $e) {
                $stats[$table] = [
                    'count' => 0,
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $stats;
    }

    /**
     * Truncate a specific table
     */
    public function truncateTable($tableName)
    {
        $allowedTables = [
            'device_sessions',
            'device_faults',
            'provision_logs',
            'tasks'
        ];

        if (!in_array($tableName, $allowedTables)) {
            throw new \Exception("Truncating table '{$tableName}' is not allowed for safety reasons.");
        }

        try {
            $db = $this->getConnection();
            $db->exec("DELETE FROM {$tableName}");
            $db->exec("DELETE FROM sqlite_sequence WHERE name='{$tableName}'");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to truncate table {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export table data as JSON
     */
    public function exportTable($tableName)
    {
        try {
            $db = $this->getConnection();
            $stmt = $db->query("SELECT * FROM {$tableName}");
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $data;
        } catch (\Exception $e) {
            Log::error("Failed to export table {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create database backup
     */
    public function createBackup()
    {
        try {
            $backupDir = storage_path('app/backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = date('Y-m-d_His');
            $backupPath = "{$backupDir}/acs_backup_{$timestamp}.db";

            if (copy($this->dbPath, $backupPath)) {
                return [
                    'success' => true,
                    'filename' => basename($backupPath),
                    'path' => $backupPath,
                    'size' => filesize($backupPath)
                ];
            }

            throw new \Exception('Failed to create backup file');
        } catch (\Exception $e) {
            Log::error('Backup creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get database file size
     */
    public function getDatabaseSize()
    {
        if (file_exists($this->dbPath)) {
            return filesize($this->dbPath);
        }
        return 0;
    }

    /**
     * Check if database file exists
     */
    public function databaseExists()
    {
        return file_exists($this->dbPath);
    }

    /**
     * Get table schema/columns
     */
    public function getTableSchema($tableName)
    {
        try {
            $db = $this->getConnection();
            $stmt = $db->query("PRAGMA table_info({$tableName})");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Log::error("Failed to get schema for {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get table records with pagination
     */
    public function getTableRecords($tableName, $page = 1, $perPage = 20)
    {
        try {
            $db = $this->getConnection();
            $offset = ($page - 1) * $perPage;
            
            // Get total count
            $countStmt = $db->query("SELECT COUNT(*) as total FROM {$tableName}");
            $total = $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];
            
            // Get paginated records
            $stmt = $db->query("SELECT * FROM {$tableName} LIMIT {$perPage} OFFSET {$offset}");
            $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return [
                'records' => $records,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get records from {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get single record
     */
    public function getRecord($tableName, $id)
    {
        try {
            $db = $this->getConnection();
            $stmt = $db->prepare("SELECT * FROM {$tableName} WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Log::error("Failed to get record from {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Insert new record
     */
    public function insertRecord($tableName, array $data)
    {
        try {
            $db = $this->getConnection();
            
            // Remove id if present (auto-increment)
            unset($data['id']);
            
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $tableName,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );
            
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($data));
            
            return $db->lastInsertId();
        } catch (\Exception $e) {
            Log::error("Failed to insert record into {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update record
     */
    public function updateRecord($tableName, $id, array $data)
    {
        try {
            $db = $this->getConnection();
            
            // Remove id from data
            unset($data['id']);
            
            $setParts = array_map(function($col) {
                return "{$col} = ?";
            }, array_keys($data));
            
            $sql = sprintf(
                "UPDATE %s SET %s WHERE id = ?",
                $tableName,
                implode(', ', $setParts)
            );
            
            $values = array_values($data);
            $values[] = $id;
            
            $stmt = $db->prepare($sql);
            return $stmt->execute($values);
        } catch (\Exception $e) {
            Log::error("Failed to update record in {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete record
     */
    public function deleteRecord($tableName, $id)
    {
        try {
            $db = $this->getConnection();
            $stmt = $db->prepare("DELETE FROM {$tableName} WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            Log::error("Failed to delete record from {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }
}
