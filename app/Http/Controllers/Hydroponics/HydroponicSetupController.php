<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\StoreHydroponicsRequest;
use App\Models\Device;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use App\Models\HydroponicYieldGrade;
use App\Services\HealthStatusService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HydroponicSetupController extends Controller
{
    protected NotificationService $notificationService;
    protected HealthStatusService $healthStatusService;

    public function __construct(
        NotificationService $notificationService,
        HealthStatusService $healthStatusService
    ) {
        $this->notificationService = $notificationService;
        $this->healthStatusService = $healthStatusService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);

        $cacheKey = "setups:user:{$user->id}:l:{$limit}:o:{$offset}";

        return Cache::tags(["user:{$user->id}", 'hydroponic_setups'])
            ->remember($cacheKey, 60, function () use ($user, $limit, $offset) {
                // Get total count before pagination
                $total = HydroponicSetup::where('user_id', $user->id)
                    ->where('harvest_status', '!=', 'harvested')
                    ->where('is_archived', false)
                    ->where('status', 'active')
                    ->count();

                // Get paginated setups
                $setups = HydroponicSetup::where('user_id', $user->id)
                    ->where('harvest_status', '!=', 'harvested')
                    ->where('is_archived', false)
                    ->where('status', 'active')
                    ->skip($offset)
                    ->take($limit)
                    ->get();

                // Calculate growth_percentage, plant_age, days_left, growth_stage, and health_status for each setup
                $setups->transform(function ($setup) {
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

                    // Calculate health_status based on sensor readings
                    $healthStatus = $this->healthStatusService->calculateHealthStatus($setup);
                    
                    // Update health_status in database if it changed and send notification
                    $oldHealthStatus = $setup->health_status;
                    if ($oldHealthStatus !== $healthStatus && $setup->harvest_status !== 'harvested') {
                        $setup->update(['health_status' => $healthStatus]);
                        
                        // Send health status change notification (only if changed to poor)
                        if ($healthStatus === 'poor') {
                            $this->notificationService->notifyHealthStatusChange($setup, $oldHealthStatus, $healthStatus);
                        }
                    }

                    $setup->plant_age = $plantAge;
                    $setup->days_left = $daysLeft;
                    $setup->growth_percentage = $growthPercentage;
                    $setup->growth_stage = $growthStage;
                    $setup->health_status = $healthStatus;

                    return $setup;
                });

                return response()->json([
                    'status' => 'success',
                    'data' => $setups,
                    'has_more' => ($offset + $limit) < $total,
                    'total' => $total,
                    'offset' => $offset,
                    'limit' => $limit,
                ]);
            });
    }

    public function show(HydroponicSetup $setup) {
        $cacheKey = "setup:{$setup->id}";

        return Cache::tags(["user:{$setup->user_id}", 'hydroponic_setups'])
            ->remember($cacheKey, 60, function () use ($setup) {
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

                // Calculate health_status based on sensor readings
                $healthStatus = $this->healthStatusService->calculateHealthStatus($setup);
                
                // Update health_status in database if it changed and send notification
                $oldHealthStatus = $setup->health_status;
                if ($oldHealthStatus !== $healthStatus && $setup->harvest_status !== 'harvested') {
                    $setup->update(['health_status' => $healthStatus]);
                    $setup->health_status = $healthStatus;
                    
                    // Send health status change notification (only if changed to poor)
                    if ($healthStatus === 'poor') {
                        $this->notificationService->notifyHealthStatusChange($setup, $oldHealthStatus, $healthStatus);
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'data' => array_merge($setup->toArray(), [
                        'plant_age' => $plantAge,
                        'days_left' => $daysLeft,
                        'growth_stage' => $growthStage,
                        'health_status' => $healthStatus,
                    ]),
                ]);
            });
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
        $validated['growth_stage'] = 'seedling';

        $setup = HydroponicSetup::create($validated);

        // Invalidate setup caches for this user
        Cache::tags(['hydroponic_setups', "user:{$user->id}"])->flush();

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
        unset($validated['device_id']);
        unset($validated['setup_date']);
        unset($validated['harvest_status']);
        unset($validated['status']);

        // Update the setup
        $setup->update($validated);

        // Invalidate setup caches for this user
        Cache::tags(['hydroponic_setups', "user:{$setup->user_id}"])->flush();

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

        // Invalidate both setup and yield caches for this user
        Cache::tags(['hydroponic_setups', "user:{$setup->user_id}"])->flush();
        Cache::tags(['hydroponic_yields', "user:{$setup->user_id}"])->flush();

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
