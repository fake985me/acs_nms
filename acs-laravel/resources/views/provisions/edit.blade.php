@extends('layouts.app')

@section('title', 'Edit Provision')

@section('content')
<div class="content-wrapper">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('provisions.index') }}" style="color: var(--primary); text-decoration: none;">&larr; Back to Provisions</a>
    </div>

    <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem;">Edit Provision</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('provisions.update', $provision) }}" method="POST">
                @csrf
                @method('PUT')

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Provision Name *</label>
                    <input type="text" name="name" class="form-input" required value="{{ old('name', $provision->name) }}">
                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="2">{{ old('description', $provision->description) }}</textarea>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Trigger Event *</label>
                    <select name="trigger_event" class="form-input" required>
                        <option value="manual" {{ old('trigger_event', $provision->trigger_event) == 'manual' ? 'selected' : '' }}>Manual Execution</option>
                        <option value="inform" {{ old('trigger_event', $provision->trigger_event) == 'inform' ? 'selected' : '' }}>On Device Inform</option>
                        <option value="boot" {{ old('trigger_event', $provision->trigger_event) == 'boot' ? 'selected' : '' }}>On Device Boot</option>
                        <option value="periodic" {{ old('trigger_event', $provision->trigger_event) == 'periodic' ? 'selected' : '' }}>Periodic (Scheduled)</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Script *</label>
                    <textarea name="script" class="form-input" rows="15" required style="font-family: monospace; font-size: 0.875rem;">{{ old('script', $provision->script) }}</textarea>
                    @error('script')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $provision->is_active) ? 'checked' : '' }} style="margin-right: 0.5rem;">
                        <span>Active</span>
                    </label>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Update Provision</button>
                    <a href="{{ route('provisions.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
