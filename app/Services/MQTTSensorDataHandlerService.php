<?php

namespace App\Services;

use App\Models\SensorReading;
use App\Models\SensorSystem;
use Illuminate\Support\Facades\DB;

class MQTTSensorDataHandlerService {
    public function handlePayload(int $deviceId, array $payload): void
    {
        DB::transaction(function () use ($deviceId, $payload) {
            foreach ($payload as $systemType => $readings) {
                // Get or create sensor system
                $sensorSystem = SensorSystem::firstOrCreate(
                    [
                        'device_id' => $deviceId,
                        'system_type' => $systemType,
                    ],
                    [
                        'name' => ucfirst(str_replace('_', ' ', $systemType)),
                        'is_active' => true,
                    ]
                );

                // Create sensor reading with normalized keys
                $readingData = [
                    'sensor_system_id' => $sensorSystem->id,
                    'reading_time' => now(),
                ];

                // Map MQTT keys to database columns
                $keyMap = [
                    'pH' => 'ph',
                    'TDS' => 'tds',
                    'Turbidity' => 'turbidity',
                    'WaterLevel' => 'water_level',
                    'Humidity' => 'humidity',
                    'Temperature' => 'temperature',
                    'EC' => 'ec',
                    'ElectricCurrent' => 'electric_current',
                ];

                foreach ($readings as $key => $value) {
                    $dbKey = $keyMap[$key] ?? strtolower($key);
                    $readingData[$dbKey] = $value;
                }

                SensorReading::create($readingData);
            }
        });
    }
}