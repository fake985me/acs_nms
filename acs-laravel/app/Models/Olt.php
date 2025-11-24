<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Olt extends Model
{
    protected $fillable = [
        'name',
        'ip_address',
        'snmp_port',
        'snmp_version',
        'snmp_community',
        'snmp_v3_username',
        'snmp_v3_auth_type',
        'snmp_v3_auth_password',
        'snmp_v3_priv_type',
        'snmp_v3_priv_password',
        'snmp_timeout',
        'web_management_port',
        'location',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'snmp_port' => 'integer',
        'snmp_timeout' => 'integer',
        'web_management_port' => 'integer',
    ];

    protected $hidden = [
        'snmp_community',
        'snmp_v3_auth_password',
        'snmp_v3_priv_password',
    ];

    public function assignments()
    {
        return $this->hasMany(OntOltAssignment::class);
    }

    public function getDevicesCountAttribute()
    {
        return $this->assignments()->count();
    }
}
