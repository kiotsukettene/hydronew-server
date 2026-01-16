<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\HydroponicSetup;
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
     * Dashboard index - returns pH levels and nearest to harvest setup
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

        // Get nearest to harvest setup
        $nearestToHarvest = $this->getNearestToHarvestSetup($user->id);

        return response()->json([
            'user' => $user->first_name ?? $user->name,
            'device_id' => $deviceId,
            'ph_levels' => $phData,
            'nearest_to_harvest' => $nearestToHarvest,
        ]);
    }

    /**
     * Get the hydroponic setup nearest to harvest
     */
    private function getNearestToHarvestSetup(int $userId): ?array
    {
        // Get active setups that are not yet harvested and have a harvest date
        $setup = HydroponicSetup::where('user_id', $userId)
            ->where('harvest_status', '!=', 'harvested')
            ->where('is_archived', false)
            ->where('status', 'active')
            ->whereNotNull('harvest_date')
            ->orderBy('harvest_date', 'asc')
            ->first();

        if (!$setup) {
            return null;
        }

        // Calculate growth percentage (same logic as HydroponicSetupController)
        $setupDate = \Carbon\Carbon::parse($setup->setup_date);
        $now = \Carbon\Carbon::now();
        $growthPercentage = 0;

        if ($setup->harvest_date) {
            $harvestDate = \Carbon\Carbon::parse($setup->harvest_date);
            $totalDays = $setupDate->diffInDays($harvestDate);
            if ($totalDays > 0) {
                $daysPassed = $setupDate->diffInDays($now);
                $growthPercentage = min(100, round((($daysPassed / $totalDays) * 100), 0));
            }
        }

        return [
            'setup_id' => $setup->id,
            'crop_name' => $setup->crop_name,
            'growth_percentage' => $growthPercentage,
        ];
    }
}