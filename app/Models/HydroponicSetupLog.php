<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HydroponicSetupLog extends Model
{
    protected $table = 'hydroponic_setup_logs';

    protected $fillable = [
        'growth_stage',
        'ph_status',
        'tds_status',
        'ec_status',
        'humidity_status',
        'health_status',
        'harvest_date',
        'system_generated',
        'notes'
    ];

    protected $casts = [
        'ph_status' => 'float',
        'tds_status' => 'float',
        'ec_status' => 'float',
        'humidity_status' => 'float',
        'system_generated' => 'boolean',
        'harvest_date' => 'datetime',
    ];

    public function hydroponic_setup()
    {
        return $this->belongsTo(HydroponicSetup::class, 'hydroponic_setup_id');
    }
}
