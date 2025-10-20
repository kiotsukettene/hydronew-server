<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HydroponicSetupController extends Controller
{
    public function index()
    {

        $user = Auth::user();

        $setups = HydroponicSetup::where('user_id', $user->id)->paginate(2);

        return response()->json([
            'status' => 'success',
            'data' => $setups
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_name' => 'required|string|max:255',
            'number_of_crops' => 'required|integer|min:1',
            'bed_size' => 'required|in:small,medium,large',
            'pump_config' => 'nullable|array',
            'nutrient_solution' => 'required|string|max:255',
            'target_ph_min' => 'required|numeric',
            'target_ph_max' => 'required|numeric',
            'target_tds_min' => 'required|integer',
            'target_tds_max' => 'required|integer',
            'water_amount' => 'required|string|max:50',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'active';
        $validated['setup_date'] = now();

        $setup = HydroponicSetup::create($validated);

        // Automatically create an initial yield record
        HydroponicYield::create([
            'hydroponic_setup_id' => $setup->id,
            'harvest_status' => 'not_harvested',
            'growth_stage' => 'seedling',
            'health_status' => 'good',
            'system_generated' => true,
        ]);

        return response()->json([
            'message' => 'Hydroponic setup created successfully.',
            'data' => $setup,
        ], 201);
    }
}
