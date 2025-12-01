<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hydroponics\UpdateActualYieldRequest;
use App\Http\Requests\Hydroponics\UpdateYieldRequest;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use App\Models\Notification;
use App\Events\NotificationBroadcast;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

    public function update(UpdateYieldRequest $request, HydroponicYield $yield)
    {
        $validated = $request->validated();
        $user = $request->user();

        // Store old values for comparison
        $oldHealthStatus = $yield->health_status;
        
        // Update the yield with validated data
        $yield->update(array_filter($validated));

        // Reload the yield to get fresh data with relationships
        $yield->load('hydroponic_setup');
        $setup = $yield->hydroponic_setup;
        
        // Get user's first device for notifications
        $device = $user->devices()->where('status', 'connected')->first();
        
        if (!$device) {
            // If no connected device, try to get any device
            $device = $user->devices()->first();
        }

        if ($device) {
            // Check if health status changed
            if (isset($validated['health_status']) && $oldHealthStatus !== $validated['health_status']) {
                
                if ($validated['health_status'] === 'good') {
                    // Health improved to good
                    $this->createNotification(
                        $user->id,
                        $device->id,
                        'Health Improved: ' . $setup->crop_name,
                        'Great news! Your crop health status has improved to GOOD. Keep up the good work!',
                        'success'
                    );
                } elseif (in_array($validated['health_status'], ['moderate', 'poor'])) {
                    // Health deteriorated to moderate or poor
                    $healthType = $validated['health_status'] === 'poor' ? 'warning' : 'warning';
                    $healthMessage = $validated['health_status'] === 'poor' 
                        ? 'Your crop health status has deteriorated to POOR. Immediate attention required!' 
                        : 'Your crop health status has changed to MODERATE. Please check your setup.';
                    
                    $this->createNotification(
                        $user->id,
                        $device->id,
                        'Health Alert: ' . $setup->crop_name,
                        $healthMessage,
                        $healthType
                    );
                }
            }

            // Check if harvest is near (within 7 days)
            if ($yield->harvest_date) {
                $now = Carbon::now()->startOfDay();
                $harvestDate = Carbon::parse($yield->harvest_date)->startOfDay();
                $daysUntilHarvest = $now->diffInDays($harvestDate, false);

                // Only notify if harvest is between 1-7 days away and not already harvested
                if ($daysUntilHarvest >= 0 && $daysUntilHarvest <= 7 && $yield->harvest_status !== 'harvested') {
                    $harvestMessage = $daysUntilHarvest === 0 
                        ? "Your {$setup->crop_name} is ready for harvest today!" 
                        : "Your {$setup->crop_name} will be ready for harvest in {$daysUntilHarvest} day(s).";
                    
                    $this->createNotification(
                        $user->id,
                        $device->id,
                        'Harvest Reminder: ' . $setup->crop_name,
                        $harvestMessage,
                        'info'
                    );
                }
            }
        }

        return response()->json([
            'message' => 'Yield updated successfully.',
            'data' => $yield,
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

    /**
     * Create a notification for the user
     */
    private function createNotification($userId, $deviceId, $title, $message, $type = 'info')
    {
        try {
            $notification = Notification::create([
                'user_id' => $userId,
                'device_id' => $deviceId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'is_read' => false,
                'created_at' => now(),
            ]);

            Log::info('Yield notification created', [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'title' => $title
            ]);

            // Broadcast the notification
            broadcast(new NotificationBroadcast($notification));

            Log::info('Yield notification broadcast dispatched successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create yield notification: ' . $e->getMessage());
        }
    }
}
