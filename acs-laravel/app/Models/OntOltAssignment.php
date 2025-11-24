<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OntOltAssignment extends Model
{
    protected $fillable = [
        'device_id',
        'olt_id',
        'pon_port',
        'ont_id_on_port',
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }
}
