<?php

namespace App\Services;

use App\Events\NotificationBroadcast;
use App\Models\Device;
use App\Models\HydroponicSetup;
use App\Models\Notification;
use App\Models\SensorReading;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification and broadcast it to the user
     */
    public function createAndBroadcast(
        int $userId,
        ?int $deviceId,
        string $title,
        string $message,
        string $type
    ): Notification {
        $notification = Notification::create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
            'created_at' => now(),
        ]);

        Log::info('Notification created by NotificationService', [
            'notification_id' => $notification->id,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
        ]);

        broadcast(new NotificationBroadcast($notification));

        return $notification;
    }

    /**
     * Check sensor thresholds and create alerts if out of range
     */
    public function checkSensorThresholds(
        SensorReading $sensorReading,
        int $deviceId,
        string $systemType
    ): void {
        // Only process clean_water and hydroponics_water
        if (!in_array($systemType, ['clean_water', 'hydroponics_water'])) {
            return;
        }

        // Get all users associated with this device (eager load to prevent N+1)
        $device = Device::with('users')->find($deviceId);
        if (!$device || $device->users->isEmpty()) {
            Log::warning('No users found for device', ['device_id' => $deviceId]);
            return;
        }

        $alerts = [];

        if ($systemType === 'clean_water') {
            $alerts = $this->checkCleanWaterThresholds($sensorReading);
        } elseif ($systemType === 'hydroponics_water') {
            $alerts = $this->checkHydroponicsWaterThresholds($sensorReading, $device);
        }

        // Send alerts to all users of this device
        foreach ($alerts as $alert) {
            foreach ($device->users as $user) {
                $this->createAndBroadcast(
                    $user->id,
                    $deviceId,
                    $alert['title'],
                    $alert['message'],
                    'warning' // Sensor alerts are warnings
                );
            }
        }
    }

    /**
     * Check clean water against PAES thresholds
     */
    private function checkCleanWaterThresholds(SensorReading $reading): array
    {
        $alerts = [];
        $thresholds = config('sensor_thresholds.clean_water');
        $ecToTdsFactor = config('sensor_thresholds.ec_to_tds_factor', 0.5);

        // Check pH
        if ($reading->ph !== null) {
            $ph = (float) $reading->ph;
            $phMin = $thresholds['ph']['min'];
            $phMax = $thresholds['ph']['max'];

            if ($ph < $phMin) {
                $alerts[] = [
                    'title' => 'pH Level Alert',
                    'message' => "Clean water pH is {$ph}, below safe limit of {$phMin}",
                ];
            } elseif ($ph > $phMax) {
                $alerts[] = [
                    'title' => 'pH Level Alert',
                    'message' => "Clean water pH is {$ph}, above safe limit of {$phMax}",
                ];
            }
        }

        // Check Turbidity
        if ($reading->turbidity !== null) {
            $turbidity = (float) $reading->turbidity;
            $turbidityMax = $thresholds['turbidity']['max'];

            if ($turbidity >= $turbidityMax) {
                $alerts[] = [
                    'title' => 'Turbidity Alert',
                    'message' => "Clean water turbidity is {$turbidity} NTU, above safe limit of {$turbidityMax} NTU",
                ];
            }
        }

        // Check TDS (calculated from EC)
        if ($reading->ec !== null) {
            $ec = (float) $reading->ec;
            $tds = $ec * $ecToTdsFactor;
            $tdsMax = $thresholds['tds']['max'];

            if ($tds >= $tdsMax) {
                $alerts[] = [
                    'title' => 'TDS Level Alert',
                    'message' => "Clean water TDS is " . round($tds, 2) . " ppm (from EC {$ec} µS/cm), above safe limit of {$tdsMax} ppm",
                ];
            }
        }

        return $alerts;
    }

    /**
     * Check hydroponics water against user-configured thresholds
     */
    private function checkHydroponicsWaterThresholds(SensorReading $reading, Device $device): array
    {
        $alerts = [];

        // Get active hydroponic setups for users of this device (use lazy loading)
        $userIds = $device->users->pluck('id')->toArray();
        $setups = HydroponicSetup::whereIn('user_id', $userIds)
            ->where('status', 'active')
            ->where('harvest_status', '!=', 'harvested')
            ->lazy();

        if ($setups->isEmpty()) {
            return $alerts;
        }

        // Use the first setup for now
        $setup = $setups->first();
        $cropName = $setup->crop_name;
        $setupId = $setup->id;

        // Check pH
        if ($reading->ph !== null) {
            $ph = (float) $reading->ph;
            $phMin = (float) $setup->target_ph_min;
            $phMax = (float) $setup->target_ph_max;

            if ($ph < $phMin) {
                $alerts[] = [
                    'title' => 'Hydroponics pH Alert',
                    'message' => "pH level is {$ph}, below target minimum of {$phMin} for Setup #{$setupId} ({$cropName})",
                ];
            } elseif ($ph > $phMax) {
                $alerts[] = [
                    'title' => 'Hydroponics pH Alert',
                    'message' => "pH level is {$ph}, above target maximum of {$phMax} for Setup #{$setupId} ({$cropName})",
                ];
            }
        }

        // Check TDS
        if ($reading->tds !== null) {
            $tds = (float) $reading->tds;
            $tdsMin = (float) $setup->target_tds_min;
            $tdsMax = (float) $setup->target_tds_max;

            if ($tds < $tdsMin) {
                $alerts[] = [
                    'title' => 'Hydroponics TDS Alert',
                    'message' => "TDS level is {$tds} ppm, below target minimum of {$tdsMin} ppm for Setup #{$setupId} ({$cropName})",
                ];
            } elseif ($tds > $tdsMax) {
                $alerts[] = [
                    'title' => 'Hydroponics TDS Alert',
                    'message' => "TDS level is {$tds} ppm, above target maximum of {$tdsMax} ppm for Setup #{$setupId} ({$cropName})",
                ];
            }
        }
        return $alerts;
    }

    /**
     * Notify user when plant growth stage changes
     */
    public function notifyGrowthStageChange(
        HydroponicSetup $setup,
        string $oldStage,
        string $newStage
    ): void {
        $userId = $setup->user_id;
        $deviceId = $setup->device_id;
        $cropName = $setup->crop_name;

        // Determine notification type and message based on new stage
        if ($newStage === 'harvest-ready') {
            $this->createAndBroadcast(
                $userId,
                $deviceId,
                'Ready to Harvest',
                "Your {$cropName} is ready to harvest!",
                'success' // Harvest ready is good news
            );
        } elseif ($newStage === 'overgrown') {
            $this->createAndBroadcast(
                $userId,
                $deviceId,
                'Overgrown Warning',
                "Your {$cropName} is overgrown. Harvest immediately!",
                'warning' // Overgrown needs attention
            );
        } else {
            // Regular growth stage transition
            $stageNames = [
                'seedling' => 'seedling',
                'vegetative' => 'vegetative',
                'flowering' => 'flowering',
            ];

            $stageName = $stageNames[$newStage] ?? $newStage;

            $this->createAndBroadcast(
                $userId,
                $deviceId,
                'Growth Update',
                "Your {$cropName} has entered the {$stageName} stage",
                'info' // Growth updates are informational
            );
        }

        Log::info('Growth stage notification sent', [
            'setup_id' => $setup->id,
            'crop_name' => $cropName,
            'old_stage' => $oldStage,
            'new_stage' => $newStage,
        ]);
    }
}

