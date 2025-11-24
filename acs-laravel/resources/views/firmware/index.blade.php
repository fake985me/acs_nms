@extends('layouts.app')

@section('title', 'Firmware Management')

@section('content')
<div class="content-wrapper">
    <div class="flex items-center justify-between mb-6">
        <h1 style="font-size: 1.875rem; font-weight: 700;">Firmware Management</h1>
        <a href="{{ route('firmware.create') }}" class="btn btn-primary">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            Upload File
        </a>
    </div>

    @if($files->isEmpty())
        <div class="card">
            <div class="card-body text-center" style="padding: 3rem;">
                <svg style="width: 64px; height: 64px; margin: 0 auto 1rem; opacity: 0.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">No Files Uploaded</h3>
                <p class="text-muted" style="margin-bottom: 1.5rem;">Upload firmware or configuration files for device deployment</p>
                <a href="{{ route('firmware.create') }}" class="btn btn-primary">Upload Your First File</a>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Version</th>
                                <th>Manufacturer</th>
                                <th>Model</th>
                                <th>Size</th>
                                <th>Upload Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $file)
                            <tr>
                                <td>
                                    <strong>{{ $file->name }}</strong>
                                    @if($file->description)
                                        <br><small class="text-muted">{{ Str::limit($file->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $file->file_type)) }}</span>
                                </td>
                                <td>{{ $file->version ?? 'N/A' }}</td>
                                <td>{{ $file->manufacturer ?? 'N/A' }}</td>
                                <td>{{ $file->model ?? 'N/A' }}</td>
                                <td>{{ $file->file_size_human }}</td>
                                <td>{{ $file->created_at->diffForHumans() }}</td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="{{ asset('storage/' . $file->file_path) }}" class="btn btn-secondary btn-sm" download>
                                            Download
                                        </a>
                                        <form action="{{ route('firmware.destroy', $file) }}" method="POST" onsubmit="return confirm('Delete this file?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
