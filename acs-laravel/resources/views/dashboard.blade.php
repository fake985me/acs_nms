@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="content-wrapper">
    <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem;">Dashboard</h1>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Total Devices</span>
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                </div>
            </div>
            <div class="stat-card-value">{{ $stats['total'] ?? 0 }}</div>
            <div class="stat-card-change">All registered devices</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, var(--dark-card), rgba(16, 185, 129, 0.1));">
            <div class="stat-card-header">
                <span class="stat-card-title">Online</span>
                <div class="stat-card-icon" style="background: linear-gradient(135deg, var(--success), #059669);">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value">{{ $stats['online'] ?? 0 }}</div>
            <div class="stat-card-change">Active in last 5 minutes</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, var(--dark-card), rgba(239, 68, 68, 0.1));">
            <div class="stat-card-header">
                <span class="stat-card-title">Offline</span>
                <div class="stat-card-icon" style="background: linear-gradient(135deg, var(--danger), #dc2626);">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value">{{ $stats['offline'] ?? 0 }}</div>
            <div class="stat-card-change">No activity recently</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, var(--dark-card), rgba(14, 165, 233, 0.1));">
            <div class="stat-card-header">
                <span class="stat-card-title">Manufacturers</span>
                <div class="stat-card-icon" style="background: linear-gradient(135deg, var(--info), var(--secondary));">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value">{{ $stats['manufacturers'] ?? 0 }}</div>
            <div class="stat-card-change">Unique vendors</div>
        </div>
    </div>

    <!-- System Status -->
    <!-- Charts Grid -->
    <div class="grid-2 mb-4">
        <!-- Manufacturer Distribution -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Manufacturer Distribution</h3>
            </div>
            <div class="card-body">
                <canvas id="manufacturerChart" style="max-height: 250px;"></canvas>
            </div>
        </div>

        <!-- Online/Offline Status Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Device Status</h3>
            </div>
            <div class="card-body">
                <canvas id="statusChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Network Activity Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Network Activity (24 Hours)</h3>
            <span class="badge badge-info">Hourly stats</span>
        </div>
        <div class="card-body">
            <canvas id="networkActivityChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <!-- Device Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Device Filters</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard') }}" id="filterForm">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label for="manufacturerFilter" style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--dark-text-secondary); margin-bottom: 0.5rem;">Manufacturer</label>
                        <select name="manufacturer" id="manufacturerFilter" class="form-select" style="width: 100%; padding: 0.5rem; background: var(--dark-bg); border: 1px solid var(--dark-border); border-radius: 0.375rem; color: var(--dark-text); font-size: 0.875rem;">
                            <option value="">All Manufacturers</option>
                            @foreach($manufacturers as $manufacturer)
                                <option value="{{ $manufacturer }}" {{ $filterManufacturer === $manufacturer ? 'selected' : '' }}>
                                    {{ $manufacturer }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="statusFilter" style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--dark-text-secondary); margin-bottom: 0.5rem;">Status</label>
                        <select name="status" id="statusFilter" class="form-select" style="width: 100%; padding: 0.5rem; background: var(--dark-bg); border: 1px solid var(--dark-border); border-radius: 0.375rem; color: var(--dark-text); font-size: 0.875rem;">
                            <option value="">All Status</option>
                            <option value="online" {{ $filterStatus === 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ $filterStatus === 'offline' ? 'selected' : '' }}>Offline</option>
                        </select>
                    </div>

                    <div>
                        <label for="modelFilter" style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--dark-text-secondary); margin-bottom: 0.5rem;">Model</label>
                        <select name="model" id="modelFilter" class="form-select" style="width: 100%; padding: 0.5rem; background: var(--dark-bg); border: 1px solid var(--dark-border); border-radius: 0.375rem; color: var(--dark-text); font-size: 0.875rem;">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                                <option value="{{ $model }}" {{ $filterModel === $model ? 'selected' : '' }}>
                                    {{ $model }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <svg style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 0.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Apply
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="flex: 1;">
                            <svg style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 0.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Reset
                        </a>
                    </div>
                </div>
                
                @if($filterManufacturer || $filterStatus || $filterModel)
                <div style="padding: 0.75rem; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 0.375rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <svg style="width: 16px; height: 16px; color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span style="font-size: 0.875rem; color: var(--dark-text-secondary);">Active filters:</span>
                        @if($filterManufacturer)
                            <span class="badge badge-primary">{{ $filterManufacturer }}</span>
                        @endif
                        @if($filterStatus)
                            <span class="badge badge-{{ $filterStatus === 'online' ? 'success' : 'danger' }}">{{ ucfirst($filterStatus) }}</span>
                        @endif
                        @if($filterModel)
                            <span class="badge badge-info">{{ $filterModel }}</span>
                        @endif
                    </div>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Recent Devices -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Devices</h3>
            <span class="badge badge-info">{{ count($recentDevices) }} devices</span>
        </div>
        <div class="card-body">
            @if(count($recentDevices) > 0)
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Device ID</th>
                            <th>Manufacturer</th>
                            <th>Model</th>
                            <th>IP Address</th>
                            <th>Last Seen</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentDevices as $device)
                        <tr>
                            <td>
                                <a href="{{ route('devices.show', $device['device_id']) }}" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                    {{ $device['device_id'] }}
                                </a>
                            </td>
                            <td>{{ $device['manufacturer'] ?? 'N/A' }}</td>
                            <td>{{ $device['model_name'] ?? 'N/A' }}</td>
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
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-muted">No devices found. Devices will appear here after connecting to ACS.</p>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manufacturer Distribution Chart
    const manufacturerCtx = document.getElementById('manufacturerChart');
    if (manufacturerCtx) {
        const manufacturers = @json($stats['by_manufacturer'] ?? []);
        const labels = Object.keys(manufacturers);
        const data = Object.values(manufacturers);
        
        new Chart(manufacturerCtx, {
            type: 'doughnut',
            data: {
                labels: labels.length > 0 ? labels : ['No Data'],
                datasets: [{
                    data: data.length > 0 ? data : [1],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                    ],
                    borderColor: 'rgba(30, 41, 59, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#94a3b8', padding: 15 }
                    }
                }
            }
        });
    }

    // Status Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Online', 'Offline'],
                datasets: [{
                    data: [{{ $stats['online'] ?? 0 }}, {{ $stats['offline'] ?? 0 }}],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                    borderColor: 'rgba(30, 41, 59, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#94a3b8', padding: 15 }
                    }
                }
            }
        });
    }

    // Network Activity Chart
    const networkActivityCtx = document.getElementById('networkActivityChart');
    if (networkActivityCtx) {
        const networkData = @json($networkActivity ?? ['labels' => [], 'online' => [], 'offline' => []]);
        
        new Chart(networkActivityCtx, {
            type: 'line',
            data: {
                labels: networkData.labels,
                datasets: [
                    {
                        label: 'Online',
                        data: networkData.online,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Offline',
                        data: networkData.offline,
                        borderColor: 'rgba(239, 68, 68, 1)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { 
                            color: '#94a3b8', 
                            padding: 15,
                            usePointStyle: true,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#f1f5f9',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(148, 163, 184, 0.2)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            title: function(context) {
                                return 'Hour: ' + context[0].label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            color: '#94a3b8',
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        }
                    },
                    x: {
                        ticks: { 
                            color: '#94a3b8',
                            maxRotation: 45,
                            minRotation: 0
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection
