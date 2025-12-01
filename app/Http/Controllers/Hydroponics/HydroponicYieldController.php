<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\UpdateActualYieldRequest;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HydroponicYieldController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();


        $setups = HydroponicSetup::where('user_id', $user->id)
            ->with('hydroponic_yields')
            ->get();


        $data = $setups->flatMap(function ($setup) {
            return $setup->hydroponic_yields->map(function ($yield) use ($setup) {
                $setupDate = Carbon::parse($setup->setup_date)->startOfDay();
                $now = Carbon::now()->startOfDay();

                $plantAge = (int) $setupDate->diffInDays($now, false);

                $daysLeft = null;
                if ($yield->harvest_date) {
                    $harvestDate = Carbon::parse($yield->harvest_date)->startOfDay();
                    $daysLeft = max(0, (int) $now->diffInDays($harvestDate, false));
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
        });

        return response()->json([
            'status' => 'success',
            'data' => $data->values(),
        ]);
    }

    public function show(HydroponicSetup $setup)
    {
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

    public function updateActualYield(UpdateActualYieldRequest $request, HydroponicYield $yield)
    {
        $validated = $request->validated();

        $yield->update([
            'actual_yield' => $validated['actual_yield'],
            'harvest_date' => $validated['harvest_date'],
            'harvest_status' => 'harvested',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Actual yield and harvest date recorded successfully.',
            'data' => $yield,
        ]);
    }
}
