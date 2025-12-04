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
    public function index()
    {

        $user = Auth::user();

        $setups = HydroponicSetup::where('user_id', $user->id)
        ->where('harvest_status', '!=', 'harvested')
        ->where('is_archived', false)
        ->where('status', 'active')
        ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $setups
        ]);
    }

    public function show(HydroponicSetup $setup) {
        $setupDate = Carbon::parse($setup->setup_date);
        $now = Carbon::now();

        $yields = $setup->hydroponic_yields->map(function ($yield) use ($setup, $setupDate, $now) {
            $plantAge = (int) $setupDate->diffInDays($now);

            $daysLeft = null;
            if ($yield->harvest_date) {
                $harvestDate = Carbon::parse($yield->harvest_date);
                $daysLeft = (int) $now->diffInDays($harvestDate, false);
            }

            return [
                'id' => $yield->id,
                'crop_name' => $setup->crop_name,
                'setup_date' => $setup->setup_date,
                'harvest_date' => $yield->harvest_date,
                'plant_age' => $plantAge,
                'days_left' => $daysLeft,
                'growth_stage' => $yield->growth_stage,
                'health_status' => $yield->health_status,
                'harvest_status' => $yield->harvest_status,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $yields,
        ]);
    }

    public function store(StoreHydroponicsRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'active';
        $validated['setup_date'] = now();
        $validated['harvest_status'] = 'not_harvested';

        $setup = HydroponicSetup::create($validated);

        return response()->json([
            'message' => 'Hydroponic setup created successfully.',
            'data' => $setup,
        ], 201);
    }
}
