@extends('layouts.app')

@section('title', 'OLT Management')

@section('content')
<div class="content-wrapper">
    <div class="flex items-center justify-between mb-6">
        <h1 style="font-size: 1.875rem; font-weight: 700;">OLT Management</h1>
        <a href="{{ route('olts.create') }}" class="btn btn-primary">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add OLT
        </a>
    </div>

    @if($olts->isEmpty())
        <div class="card">
            <div class="card-body text-center" style="padding: 3rem;">
                <svg style="width: 64px; height: 64px; margin: 0 auto 1rem; opacity: 0.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                </svg>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">No OLTs Configured</h3>
                <p class="text-muted"  style="margin-bottom: 1.5rem;">Add your first GPON OLT to start monitoring</p>
                <a href="{{ route('olts.create') }}" class="btn btn-primary">Add OLT</a>
            </div>
        </div>
    @else
        <div class="grid-2 mb-4">
            @foreach($olts as $olt)
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">{{ $olt->name }}</h3>
                    @if($olt->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-secondary">Inactive</span>
                    @endif
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 1rem;">
                        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 0.5rem; font-size: 0.875rem;">
                            <span style="color: var(--text-muted);">IP Address:</span>
                            <span style="font-family: monospace;">{{ $olt->ip_address }}</span>
                            
                            <span style="color: var(--text-muted);">SNMP:</span>
                            <span>v{{ $olt->snmp_version }} / Port {{ $olt->snmp_port }}</span>
                            
                            <span style="color: var(--text-muted);">Location:</span>
                            <span>{{ $olt->location ?? 'N/A' }}</span>
                            
                            <span style="color: var(--text-muted);">Devices:</span>
                            <span class="badge badge-info">{{ $olt->assignments_count }} ONTs</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <a href="{{ route('olts.show', $olt) }}" class="btn btn-secondary" style="flex: 1;">
                            View Details
                        </a>
                        <a href="{{ route('olts.edit', $olt) }}" class="btn btn-secondary">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        <button onclick="testConnection({{ $olt->id }}, '{{ $olt->name }}')" class="btn btn-secondary">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<script>
function testConnection(oltId, oltName) {
    if (!confirm(`Test SNMP connection to ${oltName}?`)) return;
    
    fetch(`/olts/${oltId}/test-connection`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`✓ Connection successful!\n\n${data.system_description || ''}`);
        } else {
            alert(`✗ Connection failed\n\n${data.message}`);
        }
    })
    .catch(error => {
        alert('Error testing connection: ' + error);
    });
}
</script>
@endsection
