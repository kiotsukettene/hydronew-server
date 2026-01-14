<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Device
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $serial_number
 * @property string|null $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property User $user
 * @property Collection|Notification[] $notifications
 * @property Collection|Sensor[] $sensors
 * @property Collection|TreatmentReport[] $treatment_reports
 *
 * @package App\Models
 */
class Device extends Model
{
	use HasFactory;

	protected $table = 'devices';

	protected $casts = [
		'user_id' => 'int'
	];

    protected $fillable = [
        'device_name',
        'serial_number',
        'model',
        'firmware_version',
        'status',
    ];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function getRouteKeyName()
    {
    return 'serial_number';
    }

	public function notifications()
	{
		return $this->hasMany(Notification::class);
	}

	public function sensors()
	{
		return $this->hasMany(Sensor::class);
	}

	public function treatment_reports()
	{
		return $this->hasMany(TreatmentReport::class);
	}
}
