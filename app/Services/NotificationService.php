<?php

namespace App\Services;

use App\Events\NotificationBroadcast;
use App\Models\Device;
use App\Models\HydroponicSetup;
use App\Models\Notification;
use App\Models\SensorReading;
use Illuminate\Support\Facades\Cache;
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

        $cacheKey = "sensor_threshold_violation:{$deviceId}:{$systemType}";
        $delaySeconds = config('sensor_thresholds.alert_delay_seconds', 120);
        $cacheTtl = max($delaySeconds + 60, 600); // at least 10 min so we can track episode

        if (empty($alerts)) {
            Cache::forget($cacheKey);
            return;
        }

        $now = time();
        $state = Cache::get($cacheKey);

        if ($state === null) {
            Cache::put($cacheKey, ['first_seen' => $now, 'notified_at' => null], $cacheTtl);
            return;
        }

        if ($state['notified_at'] !== null) {
            return; // already sent for this violation episode
        }

        if (($now - $state['first_seen']) < $delaySeconds) {
            return; // wait until delay has passed
        }

        // Delay passed and still in violation: send notifications and mark as notified
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
        Cache::put($cacheKey, ['first_seen' => $state['first_seen'], 'notified_at' => $now], $cacheTtl);
    }

    /**
     * Check clean water against PAES thresholds
     */
    private function checkCleanWaterThresholds(SensorReading $reading): array
    {
        $thresholds = config('sensor_thresholds.clean_water');
        $ecToTdsFactor = config('sensor_thresholds.ec_to_tds_factor', 0.5);
        
        $violations = [];

        // Check pH
        if ($reading->ph !== null) {
            $ph = (float) $reading->ph;
            $phMin = $thresholds['ph']['min'];
            $phMax = $thresholds['ph']['max'];

            if ($ph < $phMin) {
                $violations['ph'] = "pH is {$ph}, below safe limit of {$phMin}";
            } elseif ($ph > $phMax) {
                $violations['ph'] = "pH is {$ph}, above safe limit of {$phMax}";
            }
        }

        // Check Turbidity
        if ($reading->turbidity !== null) {
            $turbidity = (float) $reading->turbidity;
            $turbidityMax = $thresholds['turbidity']['max'];

            if ($turbidity >= $turbidityMax) {
                $violations['turbidity'] = "turbidity is {$turbidity} NTU, above safe limit of {$turbidityMax} NTU";
            }
        }

        // Check TDS (calculated from EC)
        if ($reading->ec !== null) {
            $ec = (float) $reading->ec;
            $tds = $ec * $ecToTdsFactor;
            $tdsMax = $thresholds['tds']['max'];

            if ($tds >= $tdsMax) {
                $violations['tds'] = "TDS is " . round($tds, 2) . " ppm (from EC {$ec} µS/cm), above safe limit of {$tdsMax} ppm";
            }
        }

        // If there are violations, combine them into a single alert (same pattern as hydroponics)
        if (!empty($violations)) {
            $paramNames = array_keys($violations);
            $paramMessages = array_values($violations);
            
            if (count($violations) === 1) {
                $title = $this->parameterDisplayName($paramNames[0]) . ' Alert';
                $message = "Clean water " . $paramMessages[0];
            } else {
                $paramList = $this->formatParameterListForMessage($paramNames);
                $title = 'Water Quality Alert';
                $message = "Clean water: {$paramList} are out of range. " . $this->formatViolationSentences($paramMessages);
            }
            
            return [[
                'title' => $title,
                'message' => $message,
            ]];
        }

        return [];
    }
    
    /**
     * Display name for a parameter key (ph -> pH, tds -> TDS, etc.)
     */
    private function parameterDisplayName(string $key): string
    {
        return match ($key) {
            'ph' => 'pH',
            'tds' => 'TDS',
            'turbidity' => 'Turbidity',
            default => ucfirst($key),
        };
    }
    
    /**
     * Format a list of parameters with proper grammar for "X and Y are..." or "X, Y, and Z are..."
     */
    private function formatParameterListForMessage(array $params): string
    {
        $display = array_map([$this, 'parameterDisplayName'], $params);
        if (count($display) === 1) {
            return $display[0];
        }
        if (count($display) === 2) {
            return $display[0] . ' and ' . $display[1];
        }
        $last = array_pop($display);
        return implode(', ', $display) . ', and ' . $last;
    }
    
    /**
     * Format violation messages as separate sentences (each capitalized, period-separated).
     */
    private function formatViolationSentences(array $paramMessages): string
    {
        $sentences = array_map(function ($msg) {
            return ucfirst(trim($msg));
        }, $paramMessages);
        return implode(' ', $sentences);
    }

    /**
     * Check hydroponics water against user-configured thresholds
     */
    private function checkHydroponicsWaterThresholds(SensorReading $reading, Device $device): array
    {
        // Get active hydroponic setups for users of this device (use lazy loading)
        $userIds = $device->users->pluck('id')->toArray();
        $setups = HydroponicSetup::whereIn('user_id', $userIds)
            ->where('status', 'active')
            ->where('harvest_status', '!=', 'harvested')
            ->lazy();

        if ($setups->isEmpty()) {
            return [];
        }

        // Use the first setup for now
        $setup = $setups->first();
        $cropName = $setup->crop_name;
        $setupId = $setup->id;
        
        $violations = [];

        // Check pH
        if ($reading->ph !== null) {
            $ph = (float) $reading->ph;
            $phMin = (float) $setup->target_ph_min;
            $phMax = (float) $setup->target_ph_max;

            if ($ph < $phMin) {
                $violations['ph'] = "pH is {$ph}, below target minimum of {$phMin}";
            } elseif ($ph > $phMax) {
                $violations['ph'] = "pH is {$ph}, above target maximum of {$phMax}";
            }
        }

        // Check TDS
        if ($reading->tds !== null) {
            $tds = (float) $reading->tds;
            $tdsMin = (float) $setup->target_tds_min;
            $tdsMax = (float) $setup->target_tds_max;

            if ($tds < $tdsMin) {
                $violations['tds'] = "TDS is {$tds} ppm, below target minimum of {$tdsMin} ppm";
            } elseif ($tds > $tdsMax) {
                $violations['tds'] = "TDS is {$tds} ppm, above target maximum of {$tdsMax} ppm";
            }
        }
        
        // If there are violations, combine them into a single alert (same pattern as clean water)
        if (!empty($violations)) {
            $paramNames = array_keys($violations);
            $paramMessages = array_values($violations);
            
            if (count($violations) === 1) {
                $title = 'Hydroponics ' . $this->parameterDisplayName($paramNames[0]) . ' Alert';
                $message = ucfirst($paramMessages[0]) . " for Setup #{$setupId} ({$cropName})";
            } else {
                $paramList = $this->formatParameterListForMessage($paramNames);
                $title = 'Hydroponics Water Quality Alert';
                $message = "Hydroponics water: {$paramList} are out of range for Setup #{$setupId} ({$cropName}). " . $this->formatViolationSentences($paramMessages);
            }
            
            return [[
                'title' => $title,
                'message' => $message,
            ]];
        }
        
        return [];
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

