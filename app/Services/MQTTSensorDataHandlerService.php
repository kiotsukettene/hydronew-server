<?php

namespace App\Services;

use App\Events\SensorDataBroadcast;
use App\Models\Device;
use App\Models\DeviceUser;
use App\Models\SensorReading;
use App\Models\SensorSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MQTTSensorDataHandlerService {
    protected MqttService $mqttService;
    protected NotificationService $notificationService;
    protected FiltrationService $filtrationService;



    public function __construct(NotificationService $notificationService, MqttService $mqttService, FiltrationService $filtrationService)
    {
        $this->mqttService = $mqttService;

        $this->notificationService = $notificationService;
        $this->filtrationService = $filtrationService;
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

                // Schedule broadcast and threshold checks to run AFTER transaction commits
                DB::afterCommit(function () use ($sensorReading, $deviceId, $systemType) {
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

                    // Check sensor thresholds and send alerts if needed
                    try {
                        $this->notificationService->checkSensorThresholds(
                            $sensorReading,
                            $deviceId,
                            $systemType
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to check sensor thresholds', [
                            'device_id' => $deviceId,
                            'system_type' => $systemType,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
            }
        });
    }

    /**
     * Handle AI classification payload from MQTT
     *
     * Expected format:
     * {
     *   "device_serial_number": "BT20120",
     *   "sensor_data": [
     *     {
     *       "water_type": "dirty_water",
     *       "sensors": {"ph": 6.82, "tds": 2.41, ...},
     *       "ai_classification": "bad",
     *       "confidence": 98.78
     *     },
     *     ...
     *   ]
     * }
     */
/**
 * Handle AI classification payload from MQTT
 */
public function handleAIClassificationPayload(array $payload): void
{
    // 1-3. Validation code (keep as is)
    $serialNumber = $payload['device_serial_number'] ?? null;

    if (!$serialNumber) {
        Log::error('AI Classification: Missing device_serial_number', ['payload' => $payload]);
        throw new \Exception('Missing device_serial_number in payload');
    }

    $device = Device::where('serial_number', $serialNumber)->first();

    if (!$device) {
        Log::error('AI Classification: Device not found', ['serial_number' => $serialNumber]);
        throw new \Exception("Device not found with serial number: {$serialNumber}");
    }

    $hasUsers = DeviceUser::where('device_id', $device->id)->exists();

    if (!$hasUsers) {
        Log::error('AI Classification: Device not connected to any user', [
            'device_id' => $device->id,
            'serial_number' => $serialNumber
        ]);
        throw new \Exception("Device {$serialNumber} is not connected to any user");
    }

    Log::info('AI Classification: Processing data', [
        'device_id' => $device->id,
        'serial_number' => $serialNumber,
        'sensor_data_count' => count($payload['sensor_data'] ?? [])
    ]);

    // 4. Process each water type's sensor data
    DB::transaction(function () use ($device, $payload) {
        foreach ($payload['sensor_data'] as $sensorData) {
            $waterType = $sensorData['water_type'];
            $sensors = $sensorData['sensors'];
            $aiClassification = $sensorData['ai_classification'] ?? null;
            $confidence = $sensorData['confidence'] ?? null;

            // 5. Find or create sensor_system
            $sensorSystem = SensorSystem::firstOrCreate(
                [
                    'device_id' => $device->id,
                    'system_type' => $waterType,
                ],
                [
                    'name' => ucfirst(str_replace('_', ' ', $waterType)),
                    'is_active' => true,
                ]
            );

            // 6. Prepare sensor reading data
            $readingData = [
                'sensor_system_id' => $sensorSystem->id,
                'reading_time' => now(),
            ];

            // 7. Map sensor keys
            $keyMap = [
                'ph' => 'ph',
                'tds' => 'tds',
                'turbidity' => 'turbidity',
                'water_level' => 'water_level',
                'humidity' => 'humidity',
                'temperature' => 'temperature',
                'ec' => 'ec',
                'electric_current' => 'electric_current',
            ];

            foreach ($sensors as $key => $value) {
                $dbKey = $keyMap[$key] ?? strtolower($key);
                $readingData[$dbKey] = $value;
            }

            // 8. Add AI classification data
            if ($aiClassification !== null) {
                $readingData['ai_classification'] = $aiClassification;
            }
            if ($confidence !== null) {
                $readingData['confidence'] = $confidence;
            }

            // 9. Create the sensor reading
            $sensorReading = SensorReading::create($readingData);

            Log::info('AI Classification: Saved reading', [
                'reading_id' => $sensorReading->id,
                'sensor_system_id' => $sensorSystem->id,
                'device_id' => $device->id,
                'water_type' => $waterType,
                'ai_classification' => $aiClassification,
                'confidence' => $confidence
            ]);

            // 9b. Check filtration auto-valve conditions (only for dirty_water)
            if ($waterType === 'dirty_water') {
                try {
                    $this->filtrationService->checkAutoValveConditions(
                        $device->id,
                        $waterType,
                        $sensors
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to check filtration auto-valve conditions', [
                        'device_id' => $device->id,
                        'water_type' => $waterType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 10. Broadcast the data - MUST BE INSIDE THE FOREACH LOOP
            DB::afterCommit(function () use ($sensorReading, $device, $waterType) {
                try {
                    Log::info('🚀 About to broadcast sensor data', [
                        'device_id' => $device->id,
                        'system_type' => $waterType,
                        'reading_id' => $sensorReading->id,
                    ]);

                    broadcast(new SensorDataBroadcast($sensorReading, $device->id, $waterType));

                    Log::info('✅ Sensor data broadcast completed', [
                        'device_id' => $device->id,
                        'system_type' => $waterType,
                        'reading_id' => $sensorReading->id,
                        'ph' => $sensorReading->ph,
                    ]);

                } catch (\Exception $e) {
                    Log::error('AI Classification: Failed to broadcast', [
                        'reading_id' => $sensorReading->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Check sensor thresholds and send alerts if needed
                try {
                    $this->notificationService->checkSensorThresholds(
                        $sensorReading,
                        $device->id,
                        $waterType
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to check sensor thresholds', [
                        'device_id' => $device->id,
                        'system_type' => $waterType,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        } // ← End of foreach loop - afterCommit must be BEFORE this
    }); // ← End of DB::transaction
}
}
