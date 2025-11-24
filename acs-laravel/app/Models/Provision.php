<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provision extends Model
{
    protected $fillable = [
        'name',
        'description',
        'script',
        'trigger_event',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getTriggerEventLabelAttribute()
    {
        $labels = [
            'manual' => 'Manual Execution',
            'inform' => 'On Device Inform',
            'boot' => 'On Device Boot',
            'periodic' => 'Periodic (Scheduled)',
        ];
        
        return $labels[$this->trigger_event] ?? 'Unknown';
    }
}
