<?php

namespace App\Http\Controllers;

use App\Models\Preset;
use App\Services\AcsApiService;
use Illuminate\Http\Request;

class PresetController extends Controller
{
    protected $acsApi;

    public function __construct(AcsApiService $acsApi)
    {
        $this->acsApi = $acsApi;
    }

    public function index()
    {
        $presets = Preset::latest()->get();
        return view('presets.index', compact('presets'));
    }

    public function create()
    {
        return view('presets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'configuration' => 'required|json',
        ]);

        $validated['configuration'] = json_decode($validated['configuration'], true);

        Preset::create($validated);

        return redirect()->route('presets.index')
            ->with('success', 'Preset created successfully');
    }

    public function edit(Preset $preset)
    {
        return view('presets.edit', compact('preset'));
    }

    public function update(Request $request, Preset $preset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'configuration' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $validated['configuration'] = json_decode($validated['configuration'], true);

        $preset->update($validated);

        return redirect()->route('presets.index')
            ->with('success', 'Preset updated successfully');
    }

    public function destroy(Preset $preset)
    {
        $preset->delete();

        return redirect()->route('presets.index')
            ->with('success', 'Preset deleted successfully');
    }

    public function apply(Request $request, Preset $preset, $deviceId)
    {
        try {
            // Create SetParameterValues task
            $response = $this->acsApi->createSetParametersTask($deviceId, $preset->configuration);

            return redirect()->back()
                ->with('success', "Preset '{$preset->name}' applied to device. Task created.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to apply preset: ' . $e->getMessage());
        }
    }
}
