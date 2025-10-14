<?php

namespace App\Http\Controllers\WaterQuality;

use App\Http\Controllers\Controller;
use App\Models\SensorReading;
use Illuminate\Http\Request;

class WaterMonitoringController extends Controller
{
    public function index()
    {

        // Required sensor types for water quality checking
        $requiredTypes = ['ph', 'turbidity', 'TDS', 'water_level'];


        // Get the latest readings by type by grouping by sensor_id
        $latestReadingsByType = SensorReading::selectRaw('max(id) as id')
            ->groupBy('sensor_id');

        // Fetch the latest readings for the required sensor types
        $readings = SensorReading::whereIn('id', $latestReadingsByType)
            ->with('sensor:id,type,unit')
            ->whereHas('sensor', function ($query) use ($requiredTypes) {
                $query->whereIn('type', $requiredTypes);
            })
            ->get()
            ->keyBy('sensor.type');

        return response()->json([
            'status' => 'success',
            'data' => $readings,
        ]);
    }
}
