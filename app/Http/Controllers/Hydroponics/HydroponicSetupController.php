<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\StoreHydroponicsRequest;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use App\Models\HydroponicYieldGrade;
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
        ->paginate(5);

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

    public function markAsHarvested(HydroponicSetup $setup)
    {
        // Check if setup belongs to the authenticated user
        if ($setup->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. This setup does not belong to you.',
            ], 403);
        }

        // Check if setup is already harvested
        if ($setup->harvest_status === 'harvested') {
            return response()->json([
                'status' => 'error',
                'message' => 'This setup has already been marked as harvested.',
            ], 400);
        }

        // Check if yield record exists for this setup
        $yield = HydroponicYield::where('hydroponic_setup_id', $setup->id)->first();

        if (!$yield) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot mark as harvested. Please fill in the yield data first.',
            ], 400);
        }

        // Check if required yield fields are filled (total_count and grades)
        if (is_null($yield->total_count)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot mark as harvested. Yield record is missing total count.',
            ], 400);
        }

        // Check if grades exist for this yield
        $gradesCount = HydroponicYieldGrade::where('hydroponic_yield_id', $yield->id)->count();

        if ($gradesCount === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot mark as harvested. Yield record is missing grade breakdown.',
            ], 400);
        }

        // Update harvest_status to 'harvested' and set harvest_date
        $setup->update([
            'harvest_status' => 'harvested',
            'harvest_date' => now()->toDateString(),
        ]);

        // Calculate plant_age and days_left for response
        $setupDate = Carbon::parse($setup->setup_date);
        $now = Carbon::now();
        $plantAge = (int) $setupDate->diffInDays($now);

        $daysLeft = 0;
        if ($setup->harvest_date) {
            $harvestDate = Carbon::parse($setup->harvest_date);
            $daysLeft = max(0, (int) $now->diffInDays($harvestDate, false));
        }

        // Load yield with grades for response
        $yield->load('grades');

        return response()->json([
            'status' => 'success',
            'message' => 'Setup marked as harvested successfully.',
            'data' => array_merge($setup->fresh()->toArray(), [
                'plant_age' => $plantAge,
                'days_left' => $daysLeft,
                'yield' => $yield,
            ]),
        ]);
    }
}
