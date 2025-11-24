@extends('layouts.app')

@section('title', 'Create Preset')

@section('content')
<div class="content-wrapper">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('presets.index') }}" style="color: var(--primary); text-decoration: none;">&larr; Back to Presets</a>
    </div>

    <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem;">Create Configuration Preset</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('presets.store') }}" method="POST">
                @csrf

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Preset Name *</label>
                    <input type="text" name="name" class="form-input" required value="{{ old('name') }}" placeholder="e.g., WiFi Standard Config">
                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="2" placeholder="Brief description of this preset">{{ old('description') }}</textarea>
                    @error('description')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Configuration (JSON) *</label>
                    <textarea name="configuration" class="form-input" rows="15" required style="font-family: monospace; font-size: 0.875rem;">{{ old('configuration', '{
  "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID": "MyNetwork",
  "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.BeaconType": "11i",
  "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.IEEE11iEncryptionModes": "AESEncryption",
  "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase": "password123"
}') }}</textarea>
                    @error('configuration')<span class="text-danger">{{ $message }}</span>@enderror
                    <small class="text-muted">JSON object with TR-069 parameter paths as keys and values to set</small>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Save Preset</button>
                    <a href="{{ route('presets.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
