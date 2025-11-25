<?php

namespace App\Http\Controllers;

use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DatabaseController extends Controller
{
    protected $dbService;

    public function __construct(DatabaseService $dbService)
    {
        $this->dbService = $dbService;
    }

    /**
     * Display database management page
     */
    public function index()
    {
        if (!$this->dbService->databaseExists()) {
            return view('database.index')->with('error', 'ACS database file not found');
        }

        $stats = $this->dbService->getTableStats();
        $dbSize = $this->dbService->getDatabaseSize();

        return view('database.index', compact('stats', 'dbSize'));
    }

    /**
     * Get table statistics as JSON
     */
    public function getStats()
    {
        try {
            $stats = $this->dbService->getTableStats();
            $dbSize = $this->dbService->getDatabaseSize();

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'dbSize' => $dbSize
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Truncate a table
     */
    public function truncate($tableName)
    {
        try {
            $this->dbService->truncateTable($tableName);
            return back()->with('success', "Table '{$tableName}' has been truncated successfully");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export table data
     */
    public function export($tableName)
    {
        try {
            $data = $this->dbService->exportTable($tableName);
            $filename = "{$tableName}_" . date('Y-m-d_His') . '.json';

            return Response::json($data, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename={$filename}"
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Create database backup
     */
    public function backup()
    {
        try {
            $result = $this->dbService->createBackup();
            return back()->with('success', "Backup created: {$result['filename']} (" . number_format($result['size'] / 1024, 2) . " KB)");
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * View table records
     */
    public function view(Request $request, $tableName)
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            
            $data = $this->dbService->getTableRecords($tableName, $page, $perPage);
            $schema = $this->dbService->getTableSchema($tableName);
            
            return view('database.view', array_merge($data, [
                'tableName' => $tableName,
                'schema' => $schema
            ]));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load table: ' . $e->getMessage());
        }
    }

    /**
     * Show create form
     */
    public function create($tableName)
    {
        try {
            $schema = $this->dbService->getTableSchema($tableName);
            return view('database.form', [
                'tableName' => $tableName,
                'schema' => $schema,
                'record' => null,
                'isEdit' => false
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load form: ' . $e->getMessage());
        }
    }

    /**
     * Store new record
     */
    public function store(Request $request, $tableName)
    {
        try {
            $data = $request->except(['_token', '_method']);
            $this->dbService->insertRecord($tableName, $data);
            return redirect()->route('database.view', $tableName)->with('success', 'Record created successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create record: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($tableName, $id)
    {
        try {
            $schema = $this->dbService->getTableSchema($tableName);
            $record = $this->dbService->getRecord($tableName, $id);
            
            if (!$record) {
                return back()->with('error', 'Record not found');
            }
            
            return view('database.form', [
                'tableName' => $tableName,
                'schema' => $schema,
                'record' => $record,
                'isEdit' => true
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load record: ' . $e->getMessage());
        }
    }

    /**
     * Update record
     */
    public function update(Request $request, $tableName, $id)
    {
        try {
            $data = $request->except(['_token', '_method']);
            $this->dbService->updateRecord($tableName, $id, $data);
            return redirect()->route('database.view', $tableName)->with('success', 'Record updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update record: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete record
     */
    public function delete($tableName, $id)
    {
        try {
            $this->dbService->deleteRecord($tableName, $id);
            return back()->with('success', 'Record deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete record: ' . $e->getMessage());
        }
    }
}
