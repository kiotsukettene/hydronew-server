<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorSystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'system_type',
        'name',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function readings()
    {
        return $this->hasMany(SensorReading::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function latestReading()
    {
        return $this->hasOne(SensorReading::class)->latestOfMany('reading_time');
    }
}
