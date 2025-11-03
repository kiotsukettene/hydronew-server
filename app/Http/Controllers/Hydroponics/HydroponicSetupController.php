<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\StoreHydroponicsRequest;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HydroponicSetupController extends Controller
{
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
