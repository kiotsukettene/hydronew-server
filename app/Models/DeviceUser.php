<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceUser extends Model
{
    protected $table = 'device_users';

    protected $fillable = [
        'user_id',
        'device_id',
        'token',
        'expires_at',
    ];

    // Optional: cast expires_at as datetime
    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
