<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HydroponicSetup
 * 
 * @property int $id
 * @property int $user_id
 * @property string $bed_size
 * @property string $water_amount
 * @property Carbon|null $setup_date
 * @property string|null $status
 * 
 * @property Collection|HydroponicYield[] $hydroponic_yields
 *
 * @package App\Models
 */
class HydroponicSetup extends Model
{
	protected $table = 'hydroponic_setup';
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'setup_date' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'bed_size',
		'water_amount',
		'setup_date',
		'status'
	];

	public function hydroponic_yields()
	{
		return $this->hasMany(HydroponicYield::class);
	}
}
