<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TreatmentStage
 * 
 * @property int $id
 * @property int $treatment_id
 * @property string $stage_name
 * @property int $stage_order
 * @property string|null $status
 * @property float|null $pH
 * @property float|null $turbidity
 * @property float|null $TDS
 * @property string|null $notes
 * 
 * @property TreatmentReport $treatment_report
 *
 * @package App\Models
 */
class TreatmentStage extends Model
{
	protected $table = 'treatment_stages';
	public $timestamps = false;

	protected $casts = [
		'treatment_id' => 'int',
		'stage_order' => 'int',
		'pH' => 'float',
		'turbidity' => 'float',
		'TDS' => 'float'
	];

	protected $fillable = [
		'treatment_id',
		'stage_name',
		'stage_order',
		'status',
		'pH',
		'turbidity',
		'TDS',
		'notes'
	];

	public function treatment_report()
	{
		return $this->belongsTo(TreatmentReport::class, 'treatment_id');
	}
}
