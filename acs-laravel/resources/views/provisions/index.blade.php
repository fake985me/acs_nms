@extends('layouts.app')

@section('title', 'Provisions')

@section('content')
<div class="content-wrapper">
    <div class="flex items-center justify-between mb-6">
        <h1 style="font-size: 1.875rem; font-weight: 700;">Automation Provisions</h1>
        <a href="{{ route('provisions.create') }}" class="btn btn-primary">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Provision
        </a>
    </div>

    <div class="card mb-4" style="background: rgba(59, 130, 246, 0.1); border-color: var(--primary);">
        <div class="card-body">
            <div style="display: flex; align-items: start; gap: 1rem;">
                <svg style="width: 24px; height: 24px; color: var(--primary); flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <strong>Note:</strong> Provision script execution engine is in basic mode. Full JavaScript interpreter will be added in future updates. Currently supports manual execution logging only.
                </div>
            </div>
        </div>
    </div>

    @if($provisions->isEmpty())
        <div class="card">
            <div class="card-body text-center" style="padding: 3rem;">
                <svg style="width: 64px; height: 64px; margin: 0 auto 1rem; opacity: 0.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">No Provisions Created</h3>
                <p class="text-muted" style="margin-bottom: 1.5rem;">Create automation scripts for device management</p>
                <a href="{{ route('provisions.create') }}" class="btn btn-primary">Create Your First Provision</a>
            </div>
        </div>
    @else
        <div class="grid-2 mb-4">
            @foreach($provisions as $provision)
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">{{ $provision->name }}</h3>
                    @if($provision->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-secondary">Inactive</span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-muted" style="margin-bottom: 1rem;">{{ $provision->description ?? 'No description' }}</p>
                    
                    <div style="margin-bottom: 1rem;">
                        <span class="badge badge-info">{{ $provision->trigger_event_label }}</span>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('provisions.edit', $provision) }}" class="btn btn-secondary" style="flex: 1;">
                            Edit
                        </a>
                        <form action="{{ route('provisions.execute', $provision) }}" method="POST" style="flex: 1;">
                            @csrf
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Execute</button>
                        </form>
                        <form action="{{ route('provisions.destroy', $provision) }}" method="POST" onsubmit="return confirm('Delete this provision?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
