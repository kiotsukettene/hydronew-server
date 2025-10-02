<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
class User extends Authenticatable implements MustVerifyEmail
{

    use HasFactory, Notifiable, HasApiTokens;
    protected $table = 'users';

    protected $casts = [
        'email_verified_at' => 'datetime',
        'first_time_login' => 'bool',
        'last_login_at' => 'datetime',
        'verification_expires_at' => 'datetime',
        'last_otp_sent_at' => 'datetime'
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
        'last_login_at',
        'verification_code',
        'verification_expires_at',
        'last_otp_sent_at',
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
