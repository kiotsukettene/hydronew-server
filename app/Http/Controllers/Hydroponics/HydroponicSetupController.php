<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\StoreHydroponicsRequest;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HydroponicSetupController extends Controller
{
    /**
     * Get the average harvest days for a given lettuce type
     */
    private function getHarvestDays(string $cropName): int
    {
        // Define harvest day ranges for each lettuce type
        $harvestRanges = [
            'Olmetie' => ['min' => 28, 'max' => 35],
            'Green Rapid' => ['min' => 25, 'max' => 30],
            'Romaine' => ['min' => 45, 'max' => 55],
            'Butterhead' => ['min' => 35, 'max' => 45],
            'Loose-leaf' => ['min' => 30, 'max' => 40],
        ];

        // Try to match the lettuce type in the crop name
        foreach ($harvestRanges as $type => $range) {
            if (stripos($cropName, $type) !== false) {
                // Return the average of min and max
                return (int) round(($range['min'] + $range['max']) / 2);
            }
        }

        // Default to 35 days if no match found
        return 35;
    }
    public function index()
    {

        $user = Auth::user();

        $setups = HydroponicSetup::where('user_id', $user->id)->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $setups
        ]);
    }

    public function store(StoreHydroponicsRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'active';
        $validated['setup_date'] = now();

        $setup = HydroponicSetup::create($validated);

        // Calculate expected harvest date based on lettuce type
        $harvestDays = $this->getHarvestDays($setup->crop_name);
        $expectedHarvestDate = Carbon::parse($setup->setup_date)->addDays($harvestDays);

        // Automatically create an initial yield record with expected harvest date
        HydroponicYield::create([
            'hydroponic_setup_id' => $setup->id,
            'harvest_status' => 'not_harvested',
            'growth_stage' => 'seedling',
            'health_status' => 'good',
            'harvest_date' => $expectedHarvestDate,
            'system_generated' => true,
        ]);

        return response()->json([
            'message' => 'Hydroponic setup created successfully.',
            'data' => $setup,
        ], 201);
    }
}
