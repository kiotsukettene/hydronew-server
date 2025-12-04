<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\StoreHydroponicsRequest;
use App\Models\HydroponicSetup;
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

        // Calculate plant_age (continues even after harvest date)
        $plantAge = (int) $setupDate->diffInDays($now);

        // Calculate days_left (0 if harvest date has passed)
        $daysLeft = 0;
        if ($setup->harvest_date) {
            $harvestDate = Carbon::parse($setup->harvest_date);
            $daysLeft = max(0, (int) $now->diffInDays($harvestDate, false));
        }

        return response()->json([
            'status' => 'success',
            'data' => array_merge($setup->toArray(), [
                'plant_age' => $plantAge,
                'days_left' => $daysLeft,
            ]),
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

        // Calculate plant_age and days_left
        $setupDate = Carbon::parse($setup->setup_date);
        $now = Carbon::now();
        $plantAge = (int) $setupDate->diffInDays($now);

        $daysLeft = 0;
        if ($setup->harvest_date) {
            $harvestDate = Carbon::parse($setup->harvest_date);
            $daysLeft = max(0, (int) $now->diffInDays($harvestDate, false));
        }

        return response()->json([
            'message' => 'Hydroponic setup created successfully.',
            'data' => array_merge($setup->toArray(), [
                'plant_age' => $plantAge,
                'days_left' => $daysLeft,
            ]),
        ], 201);
    }
}
