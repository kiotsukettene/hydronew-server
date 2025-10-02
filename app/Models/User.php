<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class User
 * 
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $profile_picture
 * @property string|null $address
 * @property bool|null $first_time_login
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Device[] $devices
 * @property Collection|Notification[] $notifications
 *
 * @package App\Models
 */
class User extends Model
{
	protected $table = 'users';

	protected $casts = [
		'email_verified_at' => 'datetime',
		'first_time_login' => 'bool'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'first_name',
		'last_name',
		'email',
		'email_verified_at',
		'password',
		'profile_picture',
		'address',
		'first_time_login',
		'remember_token'
	];

	public function devices()
	{
		return $this->hasMany(Device::class);
	}

	public function notifications()
	{
		return $this->hasMany(Notification::class);
	}
}
