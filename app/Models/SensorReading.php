<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SensorReading
 *
 * @property int $id
 * @property int $sensor_id
 * @property float $reading_value
 * @property Carbon|null $reading_time
 *
 * @property Sensor $sensor
 *
 * @package App\Models
 */
class SensorReading extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sensor_system_id',
        'ph',
        'tds',
        'turbidity',
        'water_level',
        'humidity',
        'temperature',
        'ec',
        'electric_current',
        'ai_classification',
        'confidence',
        'reading_time'
    ];

    protected $casts = [
        'ph' => 'decimal:2',
        'tds' => 'decimal:2',
        'turbidity' => 'decimal:2',
        'water_level' => 'decimal:2',
        'humidity' => 'decimal:2',
        'temperature' => 'decimal:2',
        'ec' => 'decimal:2',
        'electric_current' => 'decimal:2',
        'ai_classification' => 'string',
        'confidence' => 'decimal:2',
        'reading_time' => 'datetime',
    ];

    public function sensorSystem()
    {
        return $this->belongsTo(SensorSystem::class);
    }
}
