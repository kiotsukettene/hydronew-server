<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\StoreHydroponicsRequest;
use App\Models\Device;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use App\Models\HydroponicYieldGrade;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HydroponicSetupController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {

        $user = Auth::user();

        $setups = HydroponicSetup::where('user_id', $user->id)
        ->where('harvest_status', '!=', 'harvested')
        ->where('is_archived', false)
        ->where('status', 'active')
        ->paginate(5);

        // Calculate growth_percentage, plant_age, days_left, and growth_stage for each setup
        $setups->getCollection()->transform(function ($setup) {
            $setupDate = Carbon::parse($setup->setup_date);
            $now = Carbon::now();

            // Calculate plant_age
            $plantAge = (int) $setupDate->diffInDays($now);

            // Calculate days_left
            $daysLeft = 0;
            if ($setup->harvest_date) {
                $harvestDate = Carbon::parse($setup->harvest_date);
                $daysLeft = max(0, (int) $now->diffInDays($harvestDate, false));
            }

            // Calculate growth_percentage
            $growthPercentage = 0;
            if ($setup->harvest_date) {
                $harvestDate = Carbon::parse($setup->harvest_date);
                $totalDays = $setupDate->diffInDays($harvestDate);
                if ($totalDays > 0) {
                    $daysPassed = $setupDate->diffInDays($now);
                    $growthPercentage = min(100, round((($daysPassed / $totalDays) * 100), 0));
                }
            }

            // Calculate growth_stage based on plant age and harvest date
            $growthStage = $this->calculateGrowthStage($plantAge, $setup->harvest_date, $now);
            
            // Update growth_stage in database if it changed and send notification
            $oldStage = $setup->growth_stage;
            if ($oldStage !== $growthStage && $setup->harvest_status !== 'harvested') {
                $setup->update(['growth_stage' => $growthStage]);
                
                // Send growth stage change notification
                $this->notificationService->notifyGrowthStageChange($setup, $oldStage, $growthStage);
            }

            $setup->plant_age = $plantAge;
            $setup->days_left = $daysLeft;
            $setup->growth_percentage = $growthPercentage;
            $setup->growth_stage = $growthStage;

            return $setup;
        });

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

        // Calculate growth_stage based on plant age and harvest date
        $growthStage = $this->calculateGrowthStage($plantAge, $setup->harvest_date, $now);
        
        // Update growth_stage in database if it changed and send notification
        $oldStage = $setup->growth_stage;
        if ($oldStage !== $growthStage && $setup->harvest_status !== 'harvested') {
            $setup->update(['growth_stage' => $growthStage]);
            $setup->growth_stage = $growthStage;
            
            // Send growth stage change notification
            $this->notificationService->notifyGrowthStageChange($setup, $oldStage, $growthStage);
        }

        return response()->json([
            'status' => 'success',
            'data' => array_merge($setup->toArray(), [
                'plant_age' => $plantAge,
                'days_left' => $daysLeft,
                'growth_stage' => $growthStage,
            ]),
        ]);
    }

    public function store(StoreHydroponicsRequest $request)
    {
        $user = Auth::user();
        $device = $user->devices()->where('devices.is_archived', false)->first();


        $validated = $request->validated();

        $validated['user_id'] = Auth::id();
        $validated['device_id'] = $device->id;
        $validated['status'] = 'active';
        $validated['setup_date'] = now();
        $validated['harvest_status'] = 'not_harvested';
        $validated['growth_stage'] = 'seedling'; // New setups always start as seedling

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

    public function update(StoreHydroponicsRequest $request, HydroponicSetup $setup)
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
                'message' => 'Cannot edit a harvested setup.',
            ], 400);
        }

        $validated = $request->validated();

        // Prevent changing certain fields
        unset($validated['user_id']);
        unset($validated['device_id']); // Device cannot be changed after creation
        unset($validated['setup_date']);
        unset($validated['harvest_status']);
        unset($validated['status']);

        // Update the setup
        $setup->update($validated);

        // Calculate plant_age and days_left
        $setupDate = Carbon::parse($setup->setup_date);
        $now = Carbon::now();
        $plantAge = (int) $setupDate->diffInDays($now);

        $daysLeft = 0;
        if ($setup->harvest_date) {
            $harvestDate = Carbon::parse($setup->harvest_date);
            $daysLeft = max(0, (int) $now->diffInDays($harvestDate, false));
        }

        // Recalculate growth_stage after update and send notification if changed
        $growthStage = $this->calculateGrowthStage($plantAge, $setup->harvest_date, $now);
        $oldStage = $setup->growth_stage;
        if ($oldStage !== $growthStage) {
            $setup->update(['growth_stage' => $growthStage]);
            $setup->growth_stage = $growthStage;
            
            // Send growth stage change notification
            $this->notificationService->notifyGrowthStageChange($setup, $oldStage, $growthStage);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Hydroponic setup updated successfully.',
            'data' => array_merge($setup->fresh()->toArray(), [
                'plant_age' => $plantAge,
                'days_left' => $daysLeft,
            ]),
        ]);
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

        if ($setup->setup_date->diffInDays(now()) < 14) {
            return response()->json([
                'status' => 'error',
                'message' => 'Harvesting is not allowed until day 14.',
            ], 403);
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
            'growth_stage' => 'harvested',
            'status' => 'inactive',
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

    /**
     * Calculate growth stage based on plant age and harvest date
     * 
     * @param int $plantAge Days since setup
     * @param string|null $harvestDate Target harvest date
     * @param Carbon $now Current date
     * @return string Growth stage
     */
    private function calculateGrowthStage(int $plantAge, $harvestDate, Carbon $now): string
    {
        // If no harvest date is set, use age-based stages only
        if (!$harvestDate) {
            if ($plantAge < 14) {
                return 'seedling';
            } elseif ($plantAge < 30) {
                return 'vegetative';
            } else {
                return 'flowering';
            }
        }

        $harvestDate = Carbon::parse($harvestDate);
        $daysUntilHarvest = $now->diffInDays($harvestDate, false);

        // Check if overgrown (5+ days past harvest date)
        if ($daysUntilHarvest < -5) {
            return 'overgrown';
        }

        // Check if harvest-ready (at or past harvest date, but within 5 days)
        if ($daysUntilHarvest <= 0) {
            return 'harvest-ready';
        }

        // Age-based stages before harvest date
        if ($plantAge < 14) {
            return 'seedling';
        } elseif ($plantAge < 30) {
            return 'vegetative';
        } else {
            return 'flowering';
        }
    }
}
