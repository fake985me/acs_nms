@extends('layouts.app')

@section('title', 'Device Details')

@section('content')
<div class="content-wrapper">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('devices.index') }}" style="color: var(--primary); text-decoration: none;">&larr; Back to Devices</a>
    </div>

    <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem;">{{ $device['device_id'] }}</h1>

    <!-- Device Info Grid -->
    <div class="grid-2 mb-4">
        <div class="card">
            <h3 class="card-title">Device Information</h3>
            <div class="card-body">
                <table style="width: 100%;">
                    <tr style="border-bottom: 1px solid var(--dark-border);">
                        <td style="padding: 0.75rem 0; font-weight: 500;">Device ID</td>
                        <td style="padding: 0.75rem 0;">{{ $device['device_id'] }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--dark-border);">
                        <td style="padding: 0.75rem 0; font-weight: 500;">Manufacturer</td>
                        <td style="padding: 0.75rem 0;">{{ $device['manufacturer'] ?? 'N/A' }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--dark-border);">
                        <td style="padding: 0.75rem 0; font-weight: 500;">Model</td>
                        <td style="padding: 0.75rem 0;">{{ $device['model_name'] ?? 'N/A' }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--dark-border);">
                        <td style="padding: 0.75rem 0; font-weight: 500;">Software Version</td>
                        <td style="padding: 0.75rem 0;">{{ $device['software_version'] ?? 'N/A' }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--dark-border);">
                        <td style="padding: 0.75rem 0; font-weight: 500;">IP Address</td>
                        <td style="padding: 0.75rem 0;">{{ $device['ip_address'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem 0; font-weight: 500;">Last Seen</td>
                        <td style="padding: 0.75rem 0;">
                            @if(isset($device['last_inform_at']))
                                {{ \Carbon\Carbon::parse($device['last_inform_at'])->format('M d, Y H:i:s') }}
                                <span class="text-muted">({{ \Carbon\Carbon::parse($device['last_inform_at'])->diffForHumans() }})</span>
                            @else
                                Never
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Quick Actions</h3>
            <div class="card-body">
                <form action="{{ route('tasks.reboot', rawurlencode($device['device_id'])) }}" method="POST" style="margin-bottom: 0.75rem;">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Reboot Device
                    </button>
                </form>
                <form action="{{ route('tasks.getParameters', rawurlencode($device['device_id'])) }}" method="POST" style="margin-bottom: 0.75rem;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="width: 100%;">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh Parameters
                    </button>
                </form>
                <hr style="border: none; border-top: 1px solid var(--dark-border); margin: 1rem 0;"/>
                <button onclick="toggleLockDevice()" class="btn btn-secondary" style="width: 100%;">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Lock/Unlock (TODO)
                </button>
            </div>
        </div>
    </div>

    <!-- Parameters -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Device Parameters</h3>
            <span class="badge badge-info">{{ count($parameters) }} parameters</span>
        </div>
        <div class="card-body">
            @if(count($parameters) > 0)
                <div style="max-height: 600px; overflow-y: auto;">
                    <table>
                        <thead style="position: sticky; top: 0; background: var(--dark-card); z-index: 10;">
                            <tr>
                                <th>Parameter Name</th>
                                <th>Value</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parameters as $param)
                                <tr>
                                    <td><code>{{ $param['name'] }}</code></td>
                                    <td>{{ $param['value'] ?? 'N/A' }}</td>
                                    <td><span class="text-muted">{{ $param['type'] ?? 'string' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No parameters found. Try refreshing parameters from the device.</p>
            @endif
        </div>
    </div>

    @if($latestSignal && $signalHistory->count() > 1)
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const signalCtx = document.getElementById('signalHistoryChart');
            const history = @json($signalHistory);
            const labels = history.map(h => new Date(h.created_at).toLocaleTimeString());
            const rxData = history.map(h => h.rx_power);
            const txData = history.map(h => h.tx_power);
            new Chart(signalCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'RX Power (dBm)',
                            data: rxData,
                            borderColor: 'rgba(59, 130, 246, 1)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'TX Power (dBm)',
                            data: txData,
                            borderColor: 'rgba(16, 185, 129, 1)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { labels: { color: '#94a3b8' } }
                    },
                    scales: {
                        y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                        x: { ticks: { color: '#94a3b8', maxRotation: 45 }, grid: { display: false } }
                    }
                }
            });
        });
        </script>
    @endif
</div>
@endsection
