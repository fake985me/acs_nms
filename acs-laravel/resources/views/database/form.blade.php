@extends('layouts.app')

@section('title', ($isEdit ? 'Edit' : 'Create') . ' ' . ucwords(str_replace('_', ' ', $tableName)))

@section('content')
<div class="content-wrapper">
    <div style="margin-bottom: 2rem;">
        <a href="{{ route('database.view', $tableName) }}" style="color: var(--primary); text-decoration: none; margin-bottom: 0.5rem; display: inline-block;">&larr; Back to {{ ucwords(str_replace('_', ' ', $tableName)) }}</a>
        <h1 style="font-size: 1.875rem; font-weight: 700;">{{ $isEdit ? 'Edit' : 'Create' }} Record</h1>
    </div>

    <div class="card">
        <form action="{{ $isEdit ? route('database.update', [$tableName, $record['id']]) : route('database.store', $tableName) }}" method="POST">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            @foreach($schema as $column)
                @if($column['name'] !== 'id')
                <div class="form-group">
                    <label class="form-label">
                        {{ ucwords(str_replace('_', ' ', $column['name'])) }}
                        @if($column['notnull'])
                            <span style="color: var(--danger);">*</span>
                        @endif
                    </label>
                    
                    @php
                        $value = old($column['name'], $record[$column['name']] ?? '');
                        $type = 'text';
                        
                        // Auto-detect input type
                        if (str_contains($column['name'], 'email')) {
                            $type = 'email';
                        } elseif (str_contains($column['name'], 'password')) {
                            $type = 'password';
                        } elseif (str_contains($column['name'], 'date') || str_contains($column['name'], '_at')) {
                            $type = 'datetime-local';
                            if ($value && !str_contains($value, 'T')) {
                                $value = date('Y-m-d\TH:i', strtotime($value));
                            }
                        } elseif ($column['type'] === 'INTEGER') {
                            $type = 'number';
                        }
                        
                        $isTextarea = strlen($value) > 100 || str_contains($column['name'], 'description') || str_contains($column['name'], 'content') || str_contains($column['name'], 'script');
                    @endphp
                    
                    @if($isTextarea)
                        <textarea 
                            name="{{ $column['name'] }}" 
                            class="form-input" 
                            rows="5"
                            @if($column['notnull']) required @endif
                        >{{ $value }}</textarea>
                    @else
                        <input 
                            type="{{ $type }}" 
                            name="{{ $column['name'] }}" 
                            class="form-input" 
                            value="{{ $value }}"
                            @if($column['notnull']) required @endif
                        >
                    @endif
                    
                    <small style="color: var(--dark-text-secondary); font-size: 0.75rem;">
                        Type: {{ $column['type'] }}
                    </small>
                </div>
                @endif
            @endforeach

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $isEdit ? 'Update' : 'Create' }} Record
                </button>
                <a href="{{ route('database.view', $tableName) }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
