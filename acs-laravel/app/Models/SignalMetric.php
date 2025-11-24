<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignalMetric extends Model
{
    protected $fillable = [
        'device_id',
        'olt_id',
        'pon_port',
        'ont_id',
        'rx_power',
        'tx_power',
        'temperature',
        'voltage',
        'ber_value',
        'distance',
        'measured_at',
    ];

    protected $casts = [
        'rx_power' => 'decimal:2',
        'tx_power' => 'decimal:2',
        'temperature' => 'decimal:2',
        'voltage' => 'decimal:2',
        'ber_value' => 'decimal:10',
        'distance' => 'decimal:2',
        'measured_at' => 'datetime',
    ];
    
    public function getQualityLevelAttribute()
    {
        if ($this->rx_power >= -20) {
            return 'excellent';
        } elseif ($this->rx_power >= -24) {
            return 'good';
        } elseif ($this->rx_power >= -26) {
            return 'marginal';
        } else {
            return 'weak';
        }
    }

    public function getQualityLabelAttribute()
    {
        return ucfirst($this->quality_level);
    }

    public function getQualityBadgeColorAttribute()
    {
        $colors = [
            'excellent' => 'success',
            'good' => 'info',
            'marginal' => 'warning',
            'weak' => 'danger',
        ];
        
        return $colors[$this->quality_level] ?? 'secondary';
    }

    public function getQualityIconAttribute()
    {
        $icons = [
            'excellent' => '📶',
            'good' => '📡',
            'marginal' => '⚠️',
            'weak' => '❌',
        ];
        
        return $icons[$this->quality_level] ?? '❓';
    }
}
