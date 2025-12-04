<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\StoreYieldRequest;
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
        ->whereHas('hydroponic_yields', function ($q) {
            $q->where('harvest_status', 'harvested');
        })
        ->with(['hydroponic_yields' => function ($q) {
            $q->where('harvest_status', 'harvested')
              ->select('id', 'hydroponic_setup_id', 'harvest_date', 'growth_stage', 'health_status', 'harvest_status');
        }])
        ->withCount([
        'hydroponic_yields as harvested_yields_count' => function ($q) {
            $q->where('harvest_status', 'harvested');
        }
    ])
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
            'harvested_yield_count' => $setups->sum('harvested_yields_count'),
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

    public function storeYield(StoreYieldRequest $request, HydroponicSetup $setup)
    {
        $validated = $request->validated();

        // Check if yield already exists for this setup
        $existingYield = HydroponicYield::where('hydroponic_setup_id', $setup->id)->first();

        if ($existingYield) {
            // Update existing yield
            $existingYield->update([
                'total_count' => $validated['total_count'],
                'quality_grade' => $validated['quality_grade'],
                'total_weight' => $validated['total_weight'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Yield data updated successfully.',
                'data' => $existingYield->fresh(),
            ]);
        }

        // Create new yield record
        $yield = HydroponicYield::create([
            'hydroponic_setup_id' => $setup->id,
            'total_count' => $validated['total_count'],
            'quality_grade' => $validated['quality_grade'],
            'total_weight' => $validated['total_weight'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Yield data stored successfully.',
            'data' => $yield,
        ], 201);
    }
}
