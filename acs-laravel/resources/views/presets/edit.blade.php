@extends('layouts.app')

@section('title', 'Edit Preset')

@section('content')
<div class="content-wrapper">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('presets.index') }}" style="color: var(--primary); text-decoration: none;">&larr; Back to Presets</a>
    </div>

    <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem;">Edit Preset</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('presets.update', $preset) }}" method="POST">
                @csrf
                @method('PUT')

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Preset Name *</label>
                    <input type="text" name="name" class="form-input" required value="{{ old('name', $preset->name) }}">
                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="2">{{ old('description', $preset->description) }}</textarea>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Configuration (JSON) *</label>
                    <textarea name="configuration" class="form-input" rows="15" required style="font-family: monospace; font-size: 0.875rem;">{{ old('configuration', json_encode($preset->configuration, JSON_PRETTY_PRINT)) }}</textarea>
                    @error('configuration')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $preset->is_active) ? 'checked' : '' }} style="margin-right: 0.5rem;">
                        <span>Active</span>
                    </label>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Update Preset</button>
                    <a href="{{ route('presets.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
