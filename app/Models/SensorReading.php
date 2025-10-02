<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
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
	protected $table = 'sensor_readings';
	public $timestamps = false;

	protected $casts = [
		'sensor_id' => 'int',
		'reading_value' => 'float',
		'reading_time' => 'datetime'
	];

	protected $fillable = [
		'sensor_id',
		'reading_value',
		'reading_time'
	];

	public function sensor()
	{
		return $this->belongsTo(Sensor::class);
	}
}
