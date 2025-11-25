@extends('layouts.app')

@section('title', 'Database Management')

@section('content')
<div class="content-wrapper">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem;">Database Management</h1>
        <p style="color: var(--dark-text-secondary);">ACS SQLite Database Overview & Operations</p>
    </div>

    <!-- Database Info Card -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body">
            <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: center;">
                <div>
                    <div style="font-size: 0.875rem; color: var(--dark-text-secondary); margin-bottom: 0.25rem;">Database Size</div>
                    <div style="font-size: 1.5rem; font-weight: 700;">{{ number_format($dbSize / 1024 / 1024, 2) }} MB</div>
                </div>
                <div style="flex: 1;"></div>
                <form action="{{ route('database.backup') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Create Backup
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tables Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 1rem;">
        @foreach($stats as $tableName => $stat)
        <div class="card" style="position: relative;">
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">{{ ucwords(str_replace('_', ' ', $tableName)) }}</h3>
                        <div style="font-size: 2rem; font-weight: 700; color: {{ $stat['count'] > 0 ? 'var(--primary)' : 'var(--dark-text-secondary)' }};">
                            {{ number_format($stat['count']) }}
                        </div>
                        <div style="font-size: 0.875rem; color: var(--dark-text-secondary);">records</div>
                    </div>
                    <div>
                        @if($stat['exists'])
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Error</span>
                        @endif
                    </div>
                </div>

                @if($stat['exists'] && $stat['count'] > 0)
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="{{ route('database.view', $tableName) }}" class="btn btn-primary btn-sm" style="flex: 1; min-width: 100px;">
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        View Records
                    </a>
                    <a href="{{ route('database.export', $tableName) }}" class="btn btn-secondary btn-sm" style="flex: 1; min-width: 100px;">
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export
                    </a>
                    @if(in_array($tableName, ['device_sessions', 'device_faults', 'provision_logs', 'tasks']))
                    <button onclick="confirmTruncate('{{ $tableName }}')" class="btn btn-danger btn-sm" style="flex: 1; min-width: 100px;">
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Truncate
                    </button>
                    @endif
                </div>
                @elseif($stat['exists'])
                <div style="padding: 1rem; background: var(--dark-bg-secondary); border-radius: 4px; text-align: center; color: var(--dark-text-secondary);">
                    No records
                </div>
                @else
                <div style="padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 4px; text-align: center; color: #ef4444;">
                    Table Error
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Truncate Confirmation Modal -->
    <div id="truncateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: var(--dark-bg-secondary); border-radius: 8px; padding: 2rem; max-width: 400px; width: 100%;">
            <h3 style="margin: 0 0 1rem; font-size: 1.25rem; font-weight: 600; color: #ef4444;">
                <svg style="width: 24px; height: 24px; display: inline-block; vertical-align: middle; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Confirm Truncate
            </h3>
            <p style="color: var(--dark-text-secondary); margin-bottom: 1.5rem;">
                Are you sure you want to truncate the <strong id="truncateTableName"></strong> table? This will <strong>permanently delete all records</strong> and cannot be undone.
            </p>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button onclick="closeTruncateModal()" class="btn btn-secondary" style="flex: 1; min-width: 100px;">
                    Cancel
                </button>
                <button onclick="executeTruncate()" class="btn btn-danger" style="flex: 1; min-width: 100px;">
                    Yes, Truncate
                </button>
            </div>
        </div>
    </div>

    <form id="truncateForm" method="POST" style="display: none;">
        @csrf
    </form>
</div>

<script>
let tableToTruncate = null;

function confirmTruncate(tableName) {
    tableToTruncate = tableName;
    document.getElementById('truncateTableName').textContent = tableName;
    document.getElementById('truncateModal').style.display = 'flex';
}

function closeTruncateModal() {
    tableToTruncate = null;
    document.getElementById('truncateModal').style.display = 'none';
}

function executeTruncate() {
    if (!tableToTruncate) return;
    
    const form = document.getElementById('truncateForm');
    form.action = `/database/truncate/${encodeURIComponent(tableToTruncate)}`;
    form.submit();
}

// Close modal on backdrop click
document.getElementById('truncateModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeTruncateModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTruncateModal();
    }
});
</script>
@endsection
