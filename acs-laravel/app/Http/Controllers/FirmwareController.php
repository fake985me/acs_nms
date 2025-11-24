<?php

namespace App\Http\Controllers;

use App\Models\DeviceFile;
use App\Services\AcsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FirmwareController extends Controller
{
    protected $acsApi;

    public function __construct(AcsApiService $acsApi)
    {
        $this->acsApi = $acsApi;
    }

    public function index(Request $request)
    {
        $query = DeviceFile::query();

        if ($request->filled('type')) {
            $query->where('file_type', $request->type);
        }

        if ($request->filled('manufacturer')) {
            $query->where('manufacturer', 'LIKE', '%' . $request->manufacturer . '%');
        }

        $files = $query->latest()->get();
        
        return view('firmware.index', compact('files'));
    }

    public function create()
    {
        return view('firmware.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|max:102400', // 100MB max
            'file_type' => 'required|in:firmware,web_content,vendor_config,vendor_log',
            'version' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'model' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Store file
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('firmware', $filename, 'public');

        DeviceFile::create([
            'name' => $validated['name'],
            'file_type' => $validated['file_type'],
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'version' => $validated['version'] ?? null,
            'manufacturer' => $validated['manufacturer'] ?? null,
            'model' => $validated['model'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('firmware.index')
            ->with('success', 'File uploaded successfully');
    }

    public function destroy(DeviceFile $firmware)
    {
        // Delete physical file
        if (Storage::disk('public')->exists($firmware->file_path)) {
            Storage::disk('public')->delete($firmware->file_path);
        }

        $firmware->delete();

        return redirect()->route('firmware.index')
            ->with('success', 'File deleted successfully');
    }

    public function deploy(DeviceFile $firmware, $deviceId)
    {
        try {
            $fileUrl = url('storage/' . $firmware->file_path);
            
            $response = $this->acsApi->createDownloadTask($deviceId, [
                'url' => $fileUrl,
                'fileType' => $firmware->tr069_file_type,
                'fileSize' => $firmware->file_size,
            ]);

            return redirect()->back()
                ->with('success', "Firmware deployment task created for device");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to deploy firmware: ' . $e->getMessage());
        }
    }
}
