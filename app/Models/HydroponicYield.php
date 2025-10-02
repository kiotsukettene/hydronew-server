<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HydroponicYield
 * 
 * @property int $id
 * @property int $hydroponic_setup_id
 * @property string $plant_type
 * @property string|null $growth_stage
 * @property string|null $harvest_status
 * @property int $plant_age_days
 * @property string $health_status
 * @property Carbon|null $estimated_harvest_date
 * @property Carbon $created_at
 * 
 * @property HydroponicSetup $hydroponic_setup
 *
 * @package App\Models
 */
class HydroponicYield extends Model
{
	protected $table = 'hydroponic_yields';
	public $timestamps = false;

	protected $casts = [
		'hydroponic_setup_id' => 'int',
		'plant_age_days' => 'int',
		'estimated_harvest_date' => 'datetime'
	];

	protected $fillable = [
		'hydroponic_setup_id',
		'plant_type',
		'growth_stage',
		'harvest_status',
		'plant_age_days',
		'health_status',
		'estimated_harvest_date'
	];

	public function hydroponic_setup()
	{
		return $this->belongsTo(HydroponicSetup::class);
	}
}
