@extends('layouts.app')

@section('title', 'Devices')

@section('content')
<div class="content-wrapper">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <h1 style="font-size: 1.875rem; font-weight: 700; margin: 0;">Devices</h1>
        <div style="display: flex; gap: 0.75rem;">
            <div style="position: relative;">
                <input type="text" placeholder="Search devices..." class="form-input" style="padding-left: 2.5rem; width: 300px;" id="searchInput">
                <svg style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--dark-text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="card">
        @if(count($devices) > 0)
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Device ID</th>
                        <th>Manufacturer</th>
                        <th>Model</th>
                        <th>Software Version</th>
                        <th>IP Address</th>
                        <th>Last Seen</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="deviceTableBody">
                    @foreach($devices as $device)
                    <tr class="device-row" 
                        data-device-id="{{ strtolower($device['device_id'] ?? '') }}"
                        data-manufacturer="{{ strtolower($device['manufacturer'] ?? '') }}"
                        data-model="{{ strtolower($device['model_name'] ?? '') }}">
                        <td>
                            <a href="{{ route('devices.show', $device['device_id']) }}" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                {{ $device['device_id'] }}
                            </a>
                        </td>
                        <td>{{ $device['manufacturer'] ?? 'N/A' }}</td>
                        <td>{{ $device['model_name'] ?? 'N/A' }}</td>
                        <td>{{ $device['software_version'] ?? 'N/A' }}</td>
                        <td>{{ $device['ip_address'] ?? 'N/A' }}</td>
                        <td>
                            @if(isset($device['last_inform_at']))
                                {{ \Carbon\Carbon::parse($device['last_inform_at'])->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </td>
                        <td>
                            @php
                                $isOnline = isset($device['last_inform_at']) && 
                                    \Carbon\Carbon::parse($device['last_inform_at'])->diffInMinutes(now()) <= 5;
                            @endphp
                            <span class="badge {{ $isOnline ? 'badge-success' : 'badge-danger' }}">
                                {{ $isOnline ? 'Online' : 'Offline' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('devices.show', $device['device_id']) }}" class="btn btn-primary btn-sm">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="text-align: center; padding: 3rem; color: var(--dark-text-secondary);">
            <svg style="width: 64px; height: 64px; margin: 0 auto 1rem; opacity: 0.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
            </svg>
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">No Devices Found</h3>
            <p>Devices will appear here after they connect to the ACS server.</p>
        </div>
        @endif
    </div>
</div>

<script>
// Client-side search
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.device-row');
    
    rows.forEach(row => {
        const deviceId = row.dataset.deviceId || '';
        const manufacturer = row.dataset.manufacturer || '';
        const model = row.dataset.model || '';
        
        const matches = deviceId.includes(searchTerm) || 
                       manufacturer.includes(searchTerm) || 
                       model.includes(searchTerm);
        
        row.style.display = matches ? '' : 'none';
    });
});
</script>
@endsection
