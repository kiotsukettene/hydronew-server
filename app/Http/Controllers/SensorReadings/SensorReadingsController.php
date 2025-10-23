<?php

namespace App\Http\Controllers\SensorReadings;

use App\Events\SensorReadingsReceived;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SensorReading;
use App\Events\SensorReadingReceived;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class SensorReadingsController extends Controller
{
    // These should match the properties in your old command
    protected float $margin = 0.1;
    protected int $minInterval = 300; // 5 minutes

    public function store(Request $request)
    {
        // 1. Validate the incoming data from Firebase
        $validator = Validator::make($request->all(), [
            'ph' => 'nullable|numeric',
            'tds' => 'nullable|numeric',
            'turbidity' => 'nullable|numeric',
            'timestamp' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();
        $readingTime = Carbon::createFromTimestampMs($data['timestamp']);
        $newReadings = [];

        $sensorMap = [
            'ph' => 1,
            'tds' => 2,
            'turbidity' => 3,
        ];

        foreach ($sensorMap as $type => $sensorId) {
            if (!isset($data[$type])) {
                continue;
            }

            $newValue = (float) $data[$type];

            // 2. Fetch the *last* reading for *this* sensor
            $lastReading = SensorReading::where('sensor_id', $sensorId)
                ->orderBy('reading_time', 'desc')
                ->first();

            // 3. Apply the same skip logic as before
            if ($lastReading) {
                $valueChanged = abs($lastReading->reading_value - $newValue) >= $this->margin;
                $intervalPassed = Carbon::parse($lastReading->reading_time)->diffInSeconds($readingTime) >= $this->minInterval;

                if (!$valueChanged && !$intervalPassed) {
                    // Skip this reading
                    continue;
                }
            }

            // 4. Save the new reading
            $newReading = SensorReading::create([
                'sensor_id' => $sensorId,
                'reading_value' => $newValue,
                'reading_time' => $readingTime,
            ]);

            // 5. Fire the real-time event
            SensorReadingsReceived::dispatch($newReading);

            $newReadings[] = $newReading;
        }

        if (empty($newReadings)) {
            return response()->json(['message' => 'Readings skipped (no significant change).'], 200);
        }

        return response()->json([
            'message' => 'New readings saved.',
            'data' => $newReadings
        ], 201);
    }
}

