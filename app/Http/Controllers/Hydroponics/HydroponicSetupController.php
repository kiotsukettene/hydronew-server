<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Models\HydroponicSetup;
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
        //         cropname
        // number of crops
        // bed size -> enum small, medium, large
        // nutrient solution
        // target ph
        // target ph max
        // target tds min
        // target tds max
        // water amount

        // Automatic

        // setup date
        // status -> active
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

        $validated['status'] = 'active';
        $validated['setup_date'] = now();

        $setup = HydroponicSetup::create([
            'user_id' => Auth::id(),
            'crop_name' => $validated['crop_name'],
            'number_of_crops' => $validated['number_of_crops'],
            'bed_size' => $validated['bed_size'],
            // 'pump_config' => isset($validated['pump_config']) ? json_encode($validated['pump_config']) : null,
            'nutrient_solution' => $validated['nutrient_solution'],
            'target_ph_min' => $validated['target_ph_min'],
            'target_ph_max' => $validated['target_ph_max'],
            'target_tds_min' => $validated['target_tds_min'],
            'target_tds_max' => $validated['target_tds_max'],
            'water_amount' => $validated['water_amount'],
            'setup_date' => $validated['setup_date'],
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Hydroponic setup created successfully.',
            'data' => $setup,
        ], 201);
    }
}
