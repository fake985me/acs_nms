@extends('layouts.app')

@section('title', 'Create Provision')

@section('content')
<div class="content-wrapper">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('provisions.index') }}" style="color: var(--primary); text-decoration: none;">&larr; Back to Provisions</a>
    </div>

    <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem;">Create Automation Provision</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('provisions.store') }}" method="POST">
                @csrf

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Provision Name *</label>
                    <input type="text" name="name" class="form-input" required value="{{ old('name') }}" placeholder="e.g., Auto-configure MikroTik">
                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="2" placeholder="Brief description">{{ old('description') }}</textarea>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Trigger Event *</label>
                    <select name="trigger_event" class="form-input" required>
                        <option value="manual">Manual Execution</option>
                        <option value="inform">On Device Inform</option>
                        <option value="boot">On Device Boot</option>
                        <option value="periodic">Periodic (Scheduled)</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Script *</label>
                    <textarea name="script" class="form-input" rows="15" required style="font-family: monospace; font-size: 0.875rem;">{{ old('script', '// Example provision script
// Note: Full JavaScript engine pending implementation

// Auto-configure new MikroTik devices
if (device.manufacturer === "MikroTik") {
    setParameter("InternetGatewayDevice.ManagementServer.PeriodicInformInterval", 300);
    setParameter("System.NTP.Primary", "pool.ntp.org");
    log("MikroTik auto-configured");
}') }}</textarea>
                    @error('script')<span class="text-danger">{{ $message }}</span>@enderror
                    <small class="text-muted">JavaScript-like syntax (engine not yet implemented)</small>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Save Provision</button>
                    <a href="{{ route('provisions.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
