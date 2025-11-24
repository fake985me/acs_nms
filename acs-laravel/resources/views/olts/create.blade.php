@extends('layouts.app')

@section('title', 'Add OLT')

@section('content')
<div class="content-wrapper">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('olts.index') }}" style="color: var(--primary); text-decoration: none;">&larr; Back to OLTs</a>
    </div>

    <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem;">Add New OLT</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('olts.store') }}" method="POST">
                @csrf

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label class="form-label">OLT Name *</label>
                        <input type="text" name="name" class="form-input" required value="{{ old('name') }}">
                        @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="form-label">IP Address *</label>
                        <input type="text" name="ip_address" class="form-input" required value="{{ old('ip_address') }}">
                        @error('ip_address')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="form-label">SNMP Version *</label>
                        <select name="snmp_version" class="form-input" required onchange="toggleSnmpVersion(this.value)">
                            <option value="2c" {{ old('snmp_version') == '2c' ? 'selected' : '' }}>v2c</option>
                            <option value="3" {{ old('snmp_version') == '3' ? 'selected' : '' }}>v3</option>
                        </select>
                    </div>
                </div>

                <!-- v2c fields -->
                <div id="v2c_fields" style="margin-top: 1.5rem;">
                    <label class="form-label">Community *</label>
                    <input type="text" name="snmp_community" class="form-input" required value="{{ old('snmp_community', 'public') }}">
                    @error('snmp_community')<span class="text-danger">{{ $message }}</span>@enderror
                </div>

                <!-- v3 fields -->
                <div id="v3_fields" style="margin-top: 1.5rem; display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <label class="form-label">Security Name *</label>
                            <input type="text" name="snmp_v3_sec_name" class="form-input" value="{{ old('snmp_v3_sec_name') }}">
                            @error('snmp_v3_sec_name')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="form-label">Security Level *</label>
                            <select name="snmp_v3_sec_level" class="form-input">
                                <option value="noAuthNoPriv" {{ old('snmp_v3_sec_level') == 'noAuthNoPriv' ? 'selected' : '' }}>noAuthNoPriv</option>
                                <option value="authNoPriv" {{ old('snmp_v3_sec_level') == 'authNoPriv' ? 'selected' : '' }}>authNoPriv</option>
                                <option value="authPriv" {{ old('snmp_v3_sec_level') == 'authPriv' ? 'selected' : '' }}>authPriv</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <label class="form-label">Auth Protocol</label>
                            <select name="snmp_v3_auth_protocol" class="form-input">
                                <option value="" {{ old('snmp_v3_auth_protocol') == '' ? 'selected' : '' }}>None</option>
                                <option value="MD5" {{ old('snmp_v3_auth_protocol') == 'MD5' ? 'selected' : '' }}>MD5</option>
                                <option value="SHA1" {{ old('snmp_v3_auth_protocol') == 'SHA1' ? 'selected' : '' }}>SHA1</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Auth Password</label>
                            <input type="password" name="snmp_v3_auth_password" class="form-input">
                        </div>
                    </div>

                    <div style="margin-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <label class="form-label">Privacy Protocol</label>
                            <select name="snmp_v3_priv_protocol" class="form-input">
                                <option value="" {{ old('snmp_v3_priv_protocol') == '' ? 'selected' : '' }}>None</option>
                                <option value="DES" {{ old('snmp_v3_priv_protocol') == 'DES' ? 'selected' : '' }}>DES</option>
                                <option value="AES" {{ old('snmp_v3_priv_protocol') == 'AES' ? 'selected' : '' }}>AES</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Privacy Password</label>
                            <input type="password" name="snmp_v3_priv_password" class="form-input">
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                    <div>
                        <label class="form-label">SNMP Timeout (ms)</label>
                        <input type="number" name="snmp_timeout" class="form-input" value="{{ old('snmp_timeout', 6000) }}">
                    </div>
                    <div>
                        <label class="form-label">Web Management Port</label>
                        <input type="number" name="web_management_port" class="form-input" value="{{ old('web_management_port', 80) }}">
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-input" value="{{ old('location') }}">
                </div>

                <div style="margin-top: 1.5rem; grid-column: 1 / -1;">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="3">{{ old('notes') }}</textarea>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Save OLT</button>
                    <a href="{{ route('olts.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSnmpVersion(version) {
    const v2cFields = document.getElementById('v2c_fields');
    const v3Fields = document.getElementById('v3_fields');
    if (version === '2c') {
        v2cFields.style.display = 'block';
        v3Fields.style.display = 'none';
    } else {
        v2cFields.style.display = 'none';
        v3Fields.style.display = 'block';
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    const versionSelect = document.querySelector('select[name="snmp_version"]');
    if (versionSelect) {
        toggleSnmpVersion(versionSelect.value);
    }
});
</script>
@endsection
