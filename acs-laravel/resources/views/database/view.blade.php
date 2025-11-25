@extends('layouts.app')

@section('title', ucwords(str_replace('_', ' ', $tableName)) . ' Records')

@section('content')
<div class="content-wrapper">
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <a href="{{ route('database.index') }}" style="color: var(--primary); text-decoration: none; margin-bottom: 0.5rem; display: inline-block;">&larr; Back to Database</a>
                <h1 style="font-size: 1.875rem; font-weight: 700; margin: 0;">{{ ucwords(str_replace('_', ' ', $tableName)) }}</h1>
                <p style="color: var(--dark-text-secondary);">{{ number_format($total) }} records total</p>
            </div>
            <a href="{{ route('database.create', $tableName) }}" class="btn btn-primary">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Record
            </a>
        </div>
    </div>

    <div class="card">
        @if(count($records) > 0)
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        @foreach($schema as $column)
                            <th>{{ $column['name'] }}</th>
                        @endforeach
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                    <tr>
                        @foreach($schema as $column)
                            <td>
                                @php
                                    $value = $record[$column['name']] ?? '';
                                    $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                                @endphp
                                {{ $displayValue }}
                            </td>
                        @endforeach
                        <td>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="{{ route('database.edit', [$tableName, $record['id']]) }}" class="btn btn-secondary btn-sm">
                                    Edit
                                </a>
                                <form action="{{ route('database.delete', [$tableName, $record['id']]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($totalPages > 1)
        <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem; flex-wrap: wrap;">
            @if($page > 1)
                <a href="{{ route('database.view', ['table' => $tableName, 'page' => $page - 1]) }}" class="btn btn-secondary btn-sm">
                    Previous
                </a>
            @endif
            
            <span style="display: flex; align-items: center; padding: 0 1rem; color: var(--dark-text-secondary);">
                Page {{ $page }} of {{ $totalPages }}
            </span>
            
            @if($page < $totalPages)
                <a href="{{ route('database.view', ['table' => $tableName, 'page' => $page + 1]) }}" class="btn btn-secondary btn-sm">
                    Next
                </a>
            @endif
        </div>
        @endif
        @else
        <div style="text-align: center; padding: 3rem; color: var(--dark-text-secondary);">
            <p>No records found</p>
            <a href="{{ route('database.create', $tableName) }}" class="btn btn-primary" style="margin-top: 1rem;">
                Create First Record
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
