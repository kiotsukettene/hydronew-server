<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Sensor
 * 
 * @property int $id
 * @property int $device_id
 * @property string $type
 * @property string $unit
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property Device $device
 * @property Collection|SensorReading[] $sensor_readings
 *
 * @package App\Models
 */
class Sensor extends Model
{
	protected $table = 'sensors';

	protected $casts = [
		'device_id' => 'int'
	];

	protected $fillable = [
		'device_id',
		'type',
		'unit'
	];

	public function device()
	{
		return $this->belongsTo(Device::class);
	}

	public function sensor_readings()
	{
		return $this->hasMany(SensorReading::class);
	}
}
