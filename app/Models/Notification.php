<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Notification
 * 
 * @property int $id
 * @property int $user_id
 * @property int $device_id
 * @property string $message
 * @property bool|null $is_read
 * @property Carbon $created_at
 * 
 * @property User $user
 * @property Device $device
 *
 * @package App\Models
 */
class Notification extends Model
{
	protected $table = 'notifications';
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'device_id' => 'int',
		'is_read' => 'bool'
	];

	protected $fillable = [
		'user_id',
		'device_id',
		'message',
		'is_read'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function device()
	{
		return $this->belongsTo(Device::class);
	}
}
