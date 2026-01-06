<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    \Log::info('Channel authorization attempt', [
        'user_id' => $user->id,
        'requested_id' => $id, 
        'match' => (int) $user->id === (int) $id
    ]);

    return (int) $user->id === (int) $id;
});

// Public channel for sensor data broadcasts
// No authentication required - anyone can listen to sensor updates
Broadcast::channel('sensor.device.{deviceId}', function () {
    return true;
});

// Optional: System-specific sensor channels
// Format: sensor.device.{deviceId}.{systemType}
// Example: sensor.device.1.clean_water
Broadcast::channel('sensor.device.{deviceId}.{systemType}', function () {
    return true;
});

// Optional: Private sensor channel (requires authentication)
// Use this if you want to restrict sensor data to authenticated users
/*
Broadcast::channel('sensor.device.{deviceId}', function ($user, $deviceId) {
    // Check if user has access to this device
    return $user->devices()->where('id', $deviceId)->exists();
});
*/
