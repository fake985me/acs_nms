<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Services\SnmpService;
use Illuminate\Http\Request;

class OltController extends Controller
{
    protected $snmpService;

    public function __construct(SnmpService $snmpService)
    {
        $this->snmpService = $snmpService;
    }

    public function index()
    {
        $olts = Olt::withCount('assignments')->get();
        
        return view('olts.index', compact('olts'));
    }

    public function create()
    {
        return view('olts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'snmp_port' => 'nullable|integer|min:1|max:65535',
            'snmp_version' => 'required|in:2c,3',
            'snmp_community' => 'required_if:snmp_version,2c',
            'snmp_v3_username' => 'required_if:snmp_version,3',
            'snmp_v3_auth_type' => 'nullable|in:MD5,SHA1',
            'snmp_v3_auth_password' => 'nullable|string',
            'snmp_v3_priv_type' => 'nullable|in:DES,AES',
            'snmp_v3_priv_password' => 'nullable|string',
            'snmp_timeout' => 'nullable|integer|min:1000',
            'web_management_port' => 'nullable|integer|min:1|max:65535',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $olt = Olt::create($validated);

        return redirect()->route('olts.index')
            ->with('success', 'OLT created successfully');
    }

    public function show(Olt $olt)
    {
        $olt->load('assignments');
        
        return view('olts.show', compact('olt'));
    }

    public function edit(Olt $olt)
    {
        return view('olts.edit', compact('olt'));
    }

    public function update(Request $request, Olt $olt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'snmp_port' => 'nullable|integer|min:1|max:65535',
            'snmp_version' => 'required|in:2c,3',
            'snmp_community' => 'required_if:snmp_version,2c',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $olt->update($validated);

        return redirect()->route('olts.index')
            ->with('success', 'OLT updated successfully');
    }

    public function destroy(Olt $olt)
    {
        $olt->delete();

        return redirect()->route('olts.index')
            ->with('success', 'OLT deleted successfully');
    }

    public function testConnection(Olt $olt)
    {
        if (!$this->snmpService->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'SNMP extension not installed. Install php-snmp to enable OLT monitoring.'
            ]);
        }

        $result = $this->snmpService->testConnection(
            $olt->ip_address,
            $olt->snmp_community,
            $olt->snmp_version,
            $olt->snmp_port ?? 161,
            $olt->snmp_timeout ?? 6000
        );

        return response()->json($result);
    }
}
