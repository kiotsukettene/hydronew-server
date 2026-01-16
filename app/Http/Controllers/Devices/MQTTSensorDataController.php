<?php

namespace App\Http\Controllers\Devices;

use App\Http\Controllers\Controller;
use App\Models\SensorSystem;
use App\Services\MQTTSensorDataHandlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MQTTSensorDataController extends Controller
{
    protected $mqttHandler;

    public function __construct(MQTTSensorDataHandlerService $mqttHandler)
    {
        $this->mqttHandler = $mqttHandler;
    }

    /**
     * Receive sensor data from MQTT devices
     * 
     * Expected payload format:
     * {
     *   "device_id": 1,
     *   "data": {
     *     "dirty_water": {
     *       "pH": 7.5,
     *       "TDS": 450.25,
     *       "Turbidity": 12.5,
     *       "Temperature": 25.0
     *     },
     *     "clean_water": {
     *       "pH": 7.2,
     *       "EC": 1.5
     *     },
     *     "hydroponics_water": {
     *       "pH": 6.8,
     *       "EC": 2.1,
     *       "Temperature": 24.5
     *     }
     *   }
     * }
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'device_id' => 'required|integer|exists:devices,id',
                'data' => 'required|array',
                'data.*.pH' => 'nullable|numeric',
                'data.*.TDS' => 'nullable|numeric',
                'data.*.Turbidity' => 'nullable|numeric',
                'data.*.WaterLevel' => 'nullable|numeric',
                'data.*.Humidity' => 'nullable|numeric',
                'data.*.Temperature' => 'nullable|numeric',
                'data.*.EC' => 'nullable|numeric',
                'data.*.ElectricCurrent' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                Log::error('MQTT Sensor Data Validation Failed', [
                    'errors' => $validator->errors(),
                    'payload' => $request->all()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deviceId = $request->input('device_id');
            $data = $request->input('data');

            // Log incoming data
            Log::info('MQTT Sensor Data Received', [
                'device_id' => $deviceId,
                'systems' => array_keys($data)
            ]);

            // Process the data using the handler service
            $this->mqttHandler->handlePayload($deviceId, $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Sensor data stored successfully',
                'device_id' => $deviceId,
                'systems_processed' => count($data)
            ], 201);

        } catch (\Exception $e) {
            Log::error('MQTT Sensor Data Processing Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process sensor data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get latest sensor readings for a device
     */
    public function show($deviceId)
    {
        try {
            $sensorSystems = SensorSystem::where('device_id', $deviceId)
                ->with('latestReading')
                ->get();

            return response()->json([
                'status' => 'success',
                'device_id' => $deviceId,
                'systems' => $sensorSystems
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch sensor data', [
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch sensor data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

