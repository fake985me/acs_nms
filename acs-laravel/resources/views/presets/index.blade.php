@extends('layouts.app')

@section('title', 'Presets')

@section('content')
<div class="content-wrapper">
    <div class="flex items-center justify-between mb-6">
        <h1 style="font-size: 1.875rem; font-weight: 700;">Configuration Presets</h1>
        <a href="{{ route('presets.create') }}" class="btn btn-primary">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Preset
        </a>
    </div>

    @if($presets->isEmpty())
        <div class="card">
            <div class="card-body text-center" style="padding: 3rem;">
                <svg style="width: 64px; height: 64px; margin: 0 auto 1rem; opacity: 0.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">No Presets Created</h3>
                <p class="text-muted" style="margin-bottom: 1.5rem;">Create configuration templates for quick device setup</p>
                <a href="{{ route('presets.create') }}" class="btn btn-primary">Create Your First Preset</a>
            </div>
        </div>
    @else
        <div class="grid-2 mb-4">
            @foreach($presets as $preset)
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">{{ $preset->name }}</h3>
                    @if($preset->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-secondary">Inactive</span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-muted" style="margin-bottom: 1rem;">{{ $preset->description ?? 'No description' }}</p>
                    
                    <div style="margin-bottom: 1rem;">
                        <span class="badge badge-info">{{ $preset->parameter_count }} parameters</span>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('presets.edit', $preset) }}" class="btn btn-secondary" style="flex: 1;">
                            Edit
                        </a>
                        <form action="{{ route('presets.destroy', $preset) }}" method="POST" style="flex: 1;" onsubmit="return confirm('Delete this preset?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="width: 100%;">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
