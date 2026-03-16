<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HydroponicPumpState
 * Tracks state of pump 2 (hydroponics watering pump) for each device
 * 
 * @property int $id
 * @property int $device_id
 * @property bool $pump_2_state
 * @property float|null $pump_2_target_liters
 * @property Carbon|null $pump_2_started_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property Device $device
 *
 * @package App\Models
 */
class HydroponicPumpState extends Model
{
    use HasFactory;

    protected $table = 'hydroponic_pump_states';

    protected $casts = [
        'device_id' => 'int',
        'pump_2_state' => 'bool',
        'pump_2_target_liters' => 'float',
        'pump_2_started_at' => 'datetime',
    ];

    protected $fillable = [
        'device_id',
        'pump_2_state',
        'pump_2_target_liters',
        'pump_2_started_at',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
