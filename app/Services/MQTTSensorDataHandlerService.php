<?php

namespace App\Services;

use App\Events\SensorDataBroadcast;
use App\Models\SensorReading;
use App\Models\SensorSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MQTTSensorDataHandlerService {
    protected MqttService $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

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

                // Create the sensor reading
                $sensorReading = SensorReading::create($readingData);

                // Load the relationship for broadcasting
                $sensorReading->load('sensorSystem');

                // Schedule broadcast and MQTT publish to run AFTER transaction commits
                DB::afterCommit(function () use ($sensorReading, $deviceId, $systemType, $readings) {
                    try {
                        broadcast(new SensorDataBroadcast($sensorReading, $deviceId, $systemType));
                        
                        Log::debug('Sensor data broadcast', [
                            'device_id' => $deviceId,
                            'system_type' => $systemType,
                            'reading_id' => $sensorReading->id,
                            'ph' => $sensorReading->ph,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to broadcast sensor data', [
                            'device_id' => $deviceId,
                            'system_type' => $systemType,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Publish to MQTT for AI classification
                    try {
                        $mqttPayload = [
                            'id' => $sensorReading->id,
                            'device_id' => $deviceId,
                            'system_type' => $systemType,
                            'sensor_system_id' => $sensorReading->sensor_system_id,
                            'reading_time' => $sensorReading->reading_time->toIso8601String(),
                            'data' => $readings,
                        ];

                        $this->mqttService->publish('hydronew/ai/classification', $mqttPayload);

                        Log::info('Published sensor data to AI classification topic', [
                            'reading_id' => $sensorReading->id,
                            'device_id' => $deviceId,
                            'system_type' => $systemType,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to publish sensor data to AI classification topic', [
                            'reading_id' => $sensorReading->id,
                            'device_id' => $deviceId,
                            'system_type' => $systemType,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
            }
        });
    }
}