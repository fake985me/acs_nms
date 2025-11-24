@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="content-wrapper">
    <div class="flex items-center justify-between mb-6">
        <h1 style="font-size: 1.875rem; font-weight: 700;">System Settings</h1>
        <span class="badge badge-{{ auth()->user()->role_badge_color }}">{{ auth()->user()->role_label }}</span>
    </div>

    @if(!auth()->user()->isAdmin())
        <div class="card mb-4" style="background: rgba(239, 68, 68, 0.1); border-color: var(--danger);">
            <div class="card-body">
                <strong>⚠️ Read-Only Mode</strong><br>
                You need Admin or Super Admin role to edit settings.
            </div>
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST">
        @csrf

        <!-- General Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">General Settings</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label class="form-label">System Name</label>
                        <input type="text" name="system_name" class="form-input" value="{{ old('system_name', $settings['system_name']) }}">
                        <small class="text-muted">Display name for this ACS instance</small>
                    </div>
                    
                    <div>
                        <label class="form-label">Time Zone</label>
                        <select class="form-input" disabled>
                            <option>Asia/Jakarta (UTC+7)</option>
                        </select>
                        <small class="text-muted">Configure in .env file</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- TR-069 Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">TR-069 Settings</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label class="form-label">ACS API URL</label>
                        <input type="text" class="form-input" value="{{ $settings['acs_url'] }}" disabled>
                        <small class="text-muted">ACS server endpoint (read-only)</small>
                    </div>

                    <div>
                        <label class="form-label">Connection Timeout (seconds)</label>
                        <input type="number" name="connection_timeout" class="form-input" value="{{ old('connection_timeout', $settings['connection_timeout']) }}" min="5">
                        <small class="text-muted">Timeout for device connections</small>
                    </div>

                    <div>
                        <label class="form-label">Periodic Inform Tolerance (minutes)</label>
                        <input type="number" name="periodic_inform_tolerance" class="form-input" value="{{ old('periodic_inform_tolerance', $settings['periodic_inform_tolerance']) }}" min="1">
                        <small class="text-muted">Time before marking device offline</small>
                    </div>

                    <div>
                        <label class="form-label">Max Retry Attempts</label>
                        <input type="number" class="form-input" value="3" disabled>
                        <small class="text-muted">Maximum retries for failed operations</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">System Information</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 200px 1fr; gap: 1rem; font-size: 0.875rem;">
                    <div style="color: var(--text-muted);">Laravel Version:</div>
                    <div>{{ app()->version() }}</div>
                    
                    <div style="color: var(--text-muted);">PHP Version:</div>
                    <div>{{ PHP_VERSION }}</div>
                    
                    <div style="color: var(--text-muted);">Environment:</div>
                    <div>{{ app()->environment() }}</div>
                    
                    <div style="color: var(--text-muted);">Debug Mode:</div>
                    <div>
                        @if(config('app.debug'))
                            <span class="badge badge-warning">Enabled</span>
                        @else
                            <span class="badge badge-success">Disabled</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: flex; gap: 1rem;">
            @if(auth()->user()->isAdmin())
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            @else
                <button type="button" class="btn btn-secondary" disabled>Save Settings (Admin Required)</button>
            @endif
        </div>
    </form>
</div>
@endsection
