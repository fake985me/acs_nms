<?php

namespace App\Http\Controllers;

use App\Models\Provision;
use Illuminate\Http\Request;

class ProvisionController extends Controller
{
    public function index()
    {
        $provisions = Provision::latest()->get();
        return view('provisions.index', compact('provisions'));
    }

    public function create()
    {
        return view('provisions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'script' => 'required|string',
            'trigger_event' => 'required|in:manual,inform,boot,periodic',
        ]);

        Provision::create($validated);

        return redirect()->route('provisions.index')
            ->with('success', 'Provision created successfully');
    }

    public function edit(Provision $provision)
    {
        return view('provisions.edit', compact('provision'));
    }

    public function update(Request $request, Provision $provision)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'script' => 'required|string',
            'trigger_event' => 'required|in:manual,inform,boot,periodic',
            'is_active' => 'boolean',
        ]);

        $provision->update($validated);

        return redirect()->route('provisions.index')
            ->with('success', 'Provision updated successfully');
    }

    public function destroy(Provision $provision)
    {
        $provision->delete();

        return redirect()->route('provisions.index')
            ->with('success', 'Provision deleted successfully');
    }

    public function execute(Provision $provision)
    {
        // Simplified execution - just log for now
        // In production, this would run JavaScript interpreter
        
        return redirect()->route('provisions.index')
            ->with('success', "Provision '{$provision->name}' executed (script engine not yet implemented)");
    }
}
