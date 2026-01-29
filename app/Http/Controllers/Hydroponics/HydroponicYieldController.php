<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\StoreYieldRequest;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use App\Models\HydroponicYieldGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HydroponicYieldController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get filter parameters
        $filters = $request->only(['search', 'month', 'date_type']);

        // Get all harvested setups for the user with filters applied
        $setupsQuery = HydroponicSetup::where('user_id', $user->id)
            ->harvested()
            ->filter($filters)
            ->with(['hydroponic_yields.grades']);

        // Get all setups for statistics calculation (before pagination)
        $allHarvestedSetups = HydroponicSetup::where('user_id', $user->id)
            ->harvested()
            ->with(['hydroponic_yields.grades'])
            ->get();

        // Calculate statistics
        $statistics = $this->calculateStatistics($allHarvestedSetups);

        // Paginate the filtered results
        $setups = $setupsQuery->paginate($request->get('per_page', 10));

        // Transform data with duration calculation
        $data = $setups->getCollection()->map(function ($setup) {
            $yield = $setup->hydroponic_yields->first();

            // Calculate duration: days from setup_date to harvest_date
            $duration = 0;
            if ($setup->setup_date && $setup->harvest_date) {
                $setupDate = Carbon::parse($setup->setup_date)->startOfDay();
                $harvestDate = Carbon::parse($setup->harvest_date)->startOfDay();
                $duration = (int) $setupDate->diffInDays($harvestDate, false);
            }

            return [
                'id' => $setup->id,
                'crop_name' => $setup->crop_name,
                'number_of_crops' => $setup->number_of_crops,
                'bed_size' => $setup->bed_size,
                'setup_date' => $setup->setup_date,
                'harvest_date' => $setup->harvest_date,
                'duration_days' => $duration,
                'status' => $setup->status,
                'yield' => $yield ? [
                    'id' => $yield->id,
                    'total_count' => $yield->total_count,
                    'total_weight' => $yield->total_weight,
                    'notes' => $yield->notes,
                    'grades' => $yield->grades->map(function ($grade) {
                        return [
                            'id' => $grade->id,
                            'grade' => $grade->grade,
                            'count' => $grade->count,
                            'weight' => $grade->weight,
                        ];
                    }),
                ] : null,
            ];
        });

        // Replace the collection with transformed data
        $setups->setCollection($data);

        return response()->json([
            'status' => 'success',
            'statistics' => $statistics,
            'data' => $setups,
        ]);
    }

    /**
     * Calculate statistics for harvested setups
     *
     * @param \Illuminate\Database\Eloquent\Collection $setups
     * @return array
     */
    private function calculateStatistics($setups)
    {
        $totalHarvestedSetups = $setups->count();
        $totalSold = 0;
        $totalConsumed = 0;
        $totalDisposed = 0;

        foreach ($setups as $setup) {
            $yield = $setup->hydroponic_yields->first();

            if ($yield && $yield->grades && $yield->grades->count() > 0) {
                foreach ($yield->grades as $grade) {
                    $count = $grade->count ?? 0;
                    switch ($grade->grade) {
                        case 'selling':
                            $totalSold += $count;
                            break;
                        case 'consumption':
                            $totalConsumed += $count;
                            break;
                        case 'disposal':
                            $totalDisposed += $count;
                            break;
                    }
                }
            }
        }

        return [
            'total_harvested_setups' => $totalHarvestedSetups,
            'total_sold' => $totalSold,
            'total_consumed' => $totalConsumed,
            'total_disposed' => $totalDisposed,
        ];
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
                'harvest_date' => $setup->harvest_date,
                'plant_age' => $plantAge,
                'days_left' => $daysLeft,
                'status' => $setup->status,
                'growth_stage' => $setup->growth_stage,
                'health_status' => $setup->health_status,
                'harvest_status' => $setup->harvest_status,
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

        // Calculate disposal count (crops not harvested)
        $disposalCount = $setup->number_of_crops - $validated['total_count'];

        return DB::transaction(function () use ($validated, $setup, $disposalCount) {
            // Check if yield already exists for this setup
            $existingYield = HydroponicYield::where('hydroponic_setup_id', $setup->id)->first();

            if ($existingYield) {
                // Update existing yield
                $existingYield->update([
                    'total_count' => $validated['total_count'],
                    'total_weight' => $validated['total_weight'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

                // Delete existing grades and recreate
                $existingYield->grades()->delete();
                $yield = $existingYield;
                $isUpdate = true;
            } else {
                // Create new yield record
                $yield = HydroponicYield::create([
                    'hydroponic_setup_id' => $setup->id,
                    'total_count' => $validated['total_count'],
                    'total_weight' => $validated['total_weight'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $isUpdate = false;
            }

            // Create grade records from request
            foreach ($validated['grades'] as $gradeData) {
                HydroponicYieldGrade::create([
                    'hydroponic_yield_id' => $yield->id,
                    'grade' => $gradeData['grade'],
                    'count' => $gradeData['count'],
                    'weight' => $gradeData['weight'] ?? null,
                ]);
            }

            // Automatically create disposal grade record
            HydroponicYieldGrade::create([
                'hydroponic_yield_id' => $yield->id,
                'grade' => 'disposal',
                'count' => $disposalCount,
                'weight' => null,
            ]);

            // Load grades relationship for response
            $yield->load('grades');

        return response()->json([
                'status' => 'success',
                'message' => $isUpdate ? 'Yield data updated successfully.' : 'Yield data stored successfully.',
                'data' => [
                    'yield' => $yield,
                    'summary' => [
                        'total_crops_in_setup' => $setup->number_of_crops,
                        'total_harvested' => $validated['total_count'],
                        'total_disposed' => $disposalCount,
                    ],
                ],
            ], $isUpdate ? 200 : 201);
        });
    }
}
