<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceStatusHistory extends Model
{
    protected $table = 'device_status_history';
    
    protected $fillable = [
        'device_id',
        'status',
        'is_online',
        'reason',
        'details',
        'recorded_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'recorded_at' => 'datetime',
    ];
}
