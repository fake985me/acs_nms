@extends('layouts.app')

@section('title', 'System Monitoring')

@section('content')
<div class="content-wrapper">
    <div class="flex items-center justify-between mb-6">
        <h1 style="font-size: 1.875rem; font-weight: 700;">System Monitoring</h1>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span id="lastUpdate" class="text-muted" style="font-size: 0.875rem;">Last update: just now</span>
            <button id="refreshBtn" class="btn btn-secondary btn-sm" onclick="refreshMetrics()">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value" id="totalDevices">{{ $metrics['total_devices'] }}</div>
            <div class="stat-card-change">Managed devices</div>
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
            <div class="stat-card-value" id="onlineCount">{{ $metrics['online_count'] }}</div>
            <div class="stat-card-change"><span id="onlinePercentage">{{ $metrics['online_percentage'] }}%</span> uptime</div>
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
            <div class="stat-card-value" id="offlineCount">{{ $metrics['offline_count'] }}</div>
            <div class="stat-card-change">Needs attention</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, var(--dark-card), rgba(139, 92, 246, 0.1));">
            <div class="stat-card-header">
                <span class="stat-card-title">ACS Server</span>
                <div class="stat-card-icon" style="background: linear-gradient(135deg, var(--primary), #7c3aed);">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value" id="serverStatus">
                @if($health && isset($health['status']) && $health['status'] === 'ok')
                    OK
                @else
                    N/A
                @endif
            </div>
            <div class="stat-card-change">Health status</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid-2 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Device Status Distribution</h3>
            </div>
            <div class="card-body">
                <canvas id="statusChart" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Manufacturers</h3>
            </div>
            <div class="card-body">
                <canvas id="manufacturerChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity (placeholder) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">System Information</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 1rem; font-size: 0.875rem;">
                <div style="color: var(--text-muted);">ACS Endpoint:</div>
                <div style="font-family: monospace;">{{ env('ACS_API_URL', 'http://localhost:7547/api') }}</div>
                
                <div style="color: var(--text-muted);">Database:</div>
                <div>SQLite ({{ file_exists(base_path('../acs.db')) ? 'Connected' : 'Not Found' }})</div>
                
                <div style="color: var(--text-muted);">Auto-refresh:</div>
                <div id="autoRefreshStatus">Enabled (every 10s)</div>
            </div>
        </div>
    </div>
</div>

<script>
let statusChart, manufacturerChart;
let informRateChart, taskCompletionChart, connectionSuccessChart;
let autoRefreshInterval;

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    initPerformanceCharts();
    startAutoRefresh();
});

function initCharts() {
    // Status Chart
    const statusCtx = document.getElementById('statusChart');
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Online', 'Offline'],
            datasets: [{
                data: [{{ $metrics['online_count'] }}, {{ $metrics['offline_count'] }}],
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
                legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 15 } }
            }
        }
    });

    // Manufacturer Chart
    const mfgCtx = document.getElementById('manufacturerChart');
    const manufacturers = @json($byManufacturer);
    manufacturerChart = new Chart(mfgCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(manufacturers),
            datasets: [{
                label: 'Devices',
                data: Object.values(manufacturers),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(30, 41, 59, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: { color: '#94a3b8' },
                    grid: { color: 'rgba(148, 163, 184, 0.1)' }
                },
                x: { 
                    ticks: { color: '#94a3b8' },
                    grid: { display: false }
                }
            }
        }
    });
}

function initPerformanceCharts() {
    // Inform Rate Chart (Line)
    const informCtx = document.getElementById('informRateChart');
    const hours = Array.from({length: 24}, (_, i) => `${i}:00`);
    const informData = Array.from({length: 24}, () => Math.floor(Math.random() * 50) + 10);
    
    informRateChart = new Chart(informCtx, {
        type: 'line',
        data: {
            labels: hours,
            datasets: [{
                label: 'Informs per Hour',
                data: informData,
                borderColor: 'rgba(59, 130, 246, 1)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { labels: { color: '#94a3b8' } }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: { color: '#94a3b8' },
                    grid: { color: 'rgba(148, 163, 184, 0.1)' }
                },
                x: { 
                    ticks: { color: '#94a3b8', maxRotation: 45 },
                    grid: { display: false }
                }
            }
        }
    });

    // Task Completion Chart (Doughnut)
    const taskCtx = document.getElementById('taskCompletionChart');
    taskCompletionChart = new Chart(taskCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending', 'Failed'],
            datasets: [{
                data: [65, 25, 10],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
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
                legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 15 } }
            }
        }
    });

    // Connection Success Rate (Area Chart)
    const connCtx = document.getElementById('connectionSuccessChart');
    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const successData = [95, 97, 94, 98, 96, 99, 97];
    
    connectionSuccessChart = new Chart(connCtx, {
        type: 'line',
        data: {
            labels: days,
            datasets: [{
                label: 'Success Rate (%)',
                data: successData,
                borderColor: 'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { labels: { color: '#94a3b8' } }
            },
            scales: {
                y: { 
                    beginAtZero: false,
                    min: 90,
                    max: 100,
                    ticks: { color: '#94a3b8', callback: value => value + '%' },
                    grid: { color: 'rgba(148, 163, 184, 0.1)' }
                },
                x: { 
                    ticks: { color: '#94a3b8' },
                    grid: { display: false }
                }
            }
        }
    });
}

function refreshMetrics() {
    fetch('/monitoring/metrics')
        .then(response => response.json())
        .then(data => {
            // Update stats
            document.getElementById('totalDevices').textContent = data.stats.total || 0;
            document.getElementById('onlineCount').textContent = data.stats.online || 0;
            document.getElementById('offlineCount').textContent = data.stats.offline || 0;
            
            const total = data.stats.total || 1;
            const online = data.stats.online || 0;
            document.getElementById('onlinePercentage').textContent = Math.round(online / total * 100) + '%';
            
            // Update charts
            statusChart.data.datasets[0].data = [data.stats.online || 0, data.stats.offline || 0];
            statusChart.update();
            
            // Update timestamp
            document.getElementById('lastUpdate').textContent = 'Last update: just now';
        })
        .catch(error => console.error('Refresh failed:', error));
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(refreshMetrics, 10000); // 10 seconds
}

function stopAutoRefresh() {
    clearInterval(autoRefreshInterval);
    document.getElementById('autoRefreshStatus').textContent = 'Disabled';
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    stopAutoRefresh();
});
</script>
@endsection
