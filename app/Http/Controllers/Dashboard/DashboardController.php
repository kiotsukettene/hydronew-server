<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SensorSystem;
use App\Models\SensorReading;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get pH status based on value
     */
    private function getPhStatus(?float $value): string
    {
        if (is_null($value)) return 'Unknown';
        if ($value >= 6.0 && $value <= 7.5) return 'Good';
        if ($value < 6.0) return 'Acidic';
        return 'Alkaline';
    }

    /**
     * Dashboard index - returns only pH levels from all sensor systems
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $deviceId = $request->input('device_id', 1); // Default to device 1

        // Get all sensor systems for the device with their latest readings
        $sensorSystems = SensorSystem::where('device_id', $deviceId)
    ->where('is_active', true)
    ->where('system_type', '=', 'clean_water')
    ->with('latestReading')
    ->get();

        if ($sensorSystems->isEmpty()) {
            return response()->json([
                'message' => 'No active sensor systems found for this device.',
                'device_id' => $deviceId,
            ], 404);
        }

        // Format the response with only pH data
        $phData = [];

        foreach ($sensorSystems as $system) {
            $latestReading = $system->latestReading;

            $phData[$system->system_type] = [
                'value' => $latestReading?->ph,
                'unit' => 'pH',
                'time' => $latestReading?->reading_time,
                'status' => $this->getPhStatus($latestReading?->ph),
            ];
        }

        return response()->json([
            'user' => $user->first_name ?? $user->name,
            'device_id' => $deviceId,
            'ph_levels' => $phData,
        ]);
    }
}