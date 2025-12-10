<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HydroponicYieldGrade extends Model
{
    protected $table = 'hydroponic_yield_grades';

    protected $casts = [
        'hydroponic_yield_id' => 'integer',
        'count' => 'integer',
        'weight' => 'float',
    ];

    protected $fillable = [
        'hydroponic_yield_id',
        'grade',
        'count',
        'weight',
    ];

    public function hydroponic_yield()
    {
        return $this->belongsTo(HydroponicYield::class, 'hydroponic_yield_id');
    }
}
