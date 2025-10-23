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
        $requiredTypes = ['ph', 'tds', 'turbidity', 'water_level'];


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

        $qualityMsg = $this->safeForPlants(
            $readings->get('ph')?->reading_value,
            $readings->get('tds')?->reading_value,
            $readings->get('turbidity')?->reading_value,
            // $readings->get('ec')?->reading_value
        );

        return response()->json([
            'status' => 'success',
            'data' => $readings,
            'quality' => $qualityMsg
        ]);
    }

    protected function safeForPlants($ph, $tds, $turbidity)
    {
        if (is_null($ph) || is_null($tds) || is_null($turbidity)) {
            return 'Unknown';
        }

        // Check each condition individually
        $isPhSafe = ($ph >= 6.5 && $ph <= 8.0);
        $isTdsSafe = ($tds >= 50 && $tds <= 160);
        $isTurbiditySafe = ($turbidity <= 5);
        // $isEcSafe = ($ec >= 1.2 && $ec <= 2.5); // Corrected range

        // Check if all conditions are true
        if ($isPhSafe && $isTdsSafe && $isTurbiditySafe) {
            return 'Safe for plants';
        }

        return 'Unsafe for plants';
    }
}
