<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TreatmentReport
 * 
 * @property int $id
 * @property int $device_id
 * @property Carbon $start_time
 * @property Carbon|null $end_time
 * @property string|null $final_status
 * @property int|null $total_cycles
 * 
 * @property Device $device
 * @property Collection|TreatmentStage[] $treatment_stages
 *
 * @package App\Models
 */
class TreatmentReport extends Model
{
	protected $table = 'treatment_reports';
	public $timestamps = false;

	protected $casts = [
		'device_id' => 'int',
		'start_time' => 'datetime',
		'end_time' => 'datetime',
		'total_cycles' => 'int'
	];

	protected $fillable = [
		'device_id',
		'start_time',
		'end_time',
		'final_status',
		'total_cycles'
	];

	public function device()
	{
		return $this->belongsTo(Device::class);
	}

	public function treatment_stages()
	{
		return $this->hasMany(TreatmentStage::class, 'treatment_id');
	}
}
