@extends('layouts.app')

@section('title', 'Upload File')

@section('content')
<div class="content-wrapper">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('firmware.index') }}" style="color: var(--primary); text-decoration: none;">&larr; Back to Files</a>
    </div>

    <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem;">Upload Firmware / Configuration File</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('firmware.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">File Name *</label>
                    <input type="text" name="name" class="form-input" required value="{{ old('name') }}" placeholder="e.g., MikroTik v7.12 Firmware">
                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">File *</label>
                    <input type="file" name="file" class="form-input" required>
                    @error('file')<span class="text-danger">{{ $message }}</span>@enderror
                    <small class="text-muted">Max file size: 100MB</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div>
                        <label class="form-label">File Type *</label>
                        <select name="file_type" class="form-input" required>
                            <option value="firmware">Firmware Upgrade Image</option>
                            <option value="web_content">Web Content</option>
                            <option value="vendor_config">Vendor Configuration File</option>
                            <option value="vendor_log">Vendor Log File</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Version</label>
                        <input type="text" name="version" class="form-input" value="{{ old('version') }}" placeholder="e.g., 7.12">
                    </div>

                    <div>
                        <label class="form-label">Manufacturer</label>
                        <input type="text" name="manufacturer" class="form-input" value="{{ old('manufacturer') }}" placeholder="e.g., MikroTik">
                    </div>

                    <div>
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-input" value="{{ old('model') }}" placeholder="e.g., hAP lite">
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Brief description of this file">{{ old('description') }}</textarea>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Upload File</button>
                    <a href="{{ route('firmware.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
