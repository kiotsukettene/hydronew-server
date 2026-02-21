<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TipsSuggestion
 * 
 * @property int $id
 * @property string|null $device_id
 * @property string $system_type
 * @property string $title
 * @property string $description
 * @property string $category
 * @property array|null $insights
 * @property array|null $current_reading
 * @property array|null $statuses
 * @property array|null $missing_sensors
 * @property array|null $evidence
 * @property array|null $retrieved_context
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $expires_at
 *
 * @package App\Models
 */
class TipsSuggestion extends Model
{
	use HasFactory;

	protected $table = 'tips_suggestions';

	protected $fillable = [
		'device_id',
		'system_type',
		'title',
		'description',
		'category',
		'insights',
		'current_reading',
		'statuses',
		'missing_sensors',
		'evidence',
		'retrieved_context',
		'expires_at'
	];

	protected $casts = [
		'insights' => 'array',
		'current_reading' => 'array',
		'statuses' => 'array',
		'missing_sensors' => 'array',
		'evidence' => 'array',
		'retrieved_context' => 'array',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'expires_at' => 'datetime'
	];

	/**
	 * Check if the cached tips are expired
	 */
	public function isExpired(): bool
	{
		return $this->expires_at && $this->expires_at->isPast();
	}

	/**
	 * Get cached tips if not expired (valid for 24 hours)
	 */
	public static function getCached(?string $deviceId, string $systemType): ?self
	{
		return self::where('device_id', $deviceId)
			->where('system_type', $systemType)
			->where('expires_at', '>', now())
			->latest('created_at')
			->first();
	}
}
