<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use App\Models\SensorReading;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DashboardController extends Controller
{
    private function getPhStatus($value)
    {
        if (is_null($value)) return 'Unknown';
        if ($value >= 6.0 && $value <= 7.5) return 'Good';
        if ($value < 6.0) return 'Acidic';
        return 'Alkaline';
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Get the "pH" sensor
        $phSensor = Sensor::where('type', '=', 'ph')->first();

        if (!$phSensor) {
            return response()->json([
                'message' => 'No pH sensor found.',
            ], 404);
        }

        // Get the latest reading for that sensor
        $latestPhReading = $phSensor->sensor_readings()
            ->select('id', 'sensor_id', 'reading_value', 'reading_time')
            ->latest(column: 'reading_time')
            ->first();

        return response()->json([
            'user' => $user->first_name,
            'ph_level' => [
                'value' => $latestPhReading?->reading_value,
                'unit' => $phSensor->unit,
                'time' => $latestPhReading?->reading_time,
                'status' => $this->getPhStatus($latestPhReading?->reading_value),
            ]
        ]);
    }
}
