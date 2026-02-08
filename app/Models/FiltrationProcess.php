<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FiltrationProcess
 * 
 * @property int $id
 * @property int $device_id
 * @property int $treatment_report_id
 * @property string $status
 * @property bool $pump_3_state
 * @property bool $valve_1_state
 * @property bool $valve_2_state
 * @property Carbon|null $stage_1_started_at
 * @property Carbon|null $stages_2_4_started_at
 * @property int $restart_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property Device $device
 * @property TreatmentReport $treatment_report
 *
 * @package App\Models
 */
class FiltrationProcess extends Model
{
    use HasFactory;

    protected $table = 'filtration_processes';

    protected $casts = [
        'device_id' => 'int',
        'treatment_report_id' => 'int',
        'pump_3_state' => 'bool',
        'valve_1_state' => 'bool',
        'valve_2_state' => 'bool',
        'stage_1_started_at' => 'datetime',
        'stages_2_4_started_at' => 'datetime',
        'restart_count' => 'int'
    ];

    protected $fillable = [
        'device_id',
        'treatment_report_id',
        'status',
        'pump_3_state',
        'valve_1_state',
        'valve_2_state',
        'stage_1_started_at',
        'stages_2_4_started_at',
        'restart_count'
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function treatment_report()
    {
        return $this->belongsTo(TreatmentReport::class);
    }
}
