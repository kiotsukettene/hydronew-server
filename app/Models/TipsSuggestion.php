<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TipsSuggestion
 * 
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $category
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class TipsSuggestion extends Model
{
	protected $table = 'tips_suggestions';
	public $timestamps = false;

	protected $fillable = [
		'title',
		'description',
		'category'
	];
}
