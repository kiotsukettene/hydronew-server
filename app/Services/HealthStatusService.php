<?php

namespace App\Services;

use App\Models\HydroponicSetup;
use App\Models\SensorReading;
use App\Models\SensorSystem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HealthStatusService
{
    /**
     * Calculate health status for a hydroponic setup based on recent sensor readings
     *
     * @param HydroponicSetup $setup
     * @return string 'good', 'moderate', or 'poor'
     */
    public function calculateHealthStatus(HydroponicSetup $setup): string
    {
        try {
            // Get sensor readings for this setup
            $readings = $this->getSensorReadingsForSetup($setup);

            // If no readings or insufficient data, return moderate (unknown state)
            if ($readings->isEmpty() || $readings->count() < 10) {
                Log::info("Insufficient sensor data for setup {$setup->id}", [
                    'reading_count' => $readings->count()
                ]);
                return 'moderate';
            }

            // Evaluate readings against target ranges
            $evaluation = $this->evaluateReadings($readings, $setup);

            // Determine health status based on evaluation
            $healthStatus = $this->determineHealthStatus(
                $evaluation['ph_in_range_percent'],
                $evaluation['tds_in_range_percent'],
                $evaluation['total_readings']
            );

            Log::info("Health status calculated for setup {$setup->id}", [
                'crop_name' => $setup->crop_name,
                'health_status' => $healthStatus,
                'ph_in_range' => $evaluation['ph_in_range_percent'] . '%',
                'tds_in_range' => $evaluation['tds_in_range_percent'] . '%',
                'total_readings' => $evaluation['total_readings']
            ]);

            return $healthStatus;
        } catch (\Exception $e) {
            Log::error("Error calculating health status for setup {$setup->id}: " . $e->getMessage());
            return 'moderate'; // Default to moderate on error
        }
    }

    /**
     * Get sensor readings for a hydroponic setup from the last N hours
     *
     * @param HydroponicSetup $setup
     * @param int $hours Number of hours to look back
     * @return Collection
     */
    public function getSensorReadingsForSetup(HydroponicSetup $setup, int $hours = 24): Collection
    {
        // Get the device associated with this setup
        $deviceId = $setup->device_id;
        
        if (!$deviceId) {
            Log::warning("No device associated with setup {$setup->id}");
            return collect([]);
        }

        // Find the hydroponics_water sensor system for this device
        $sensorSystem = SensorSystem::where('device_id', $deviceId)
            ->where('system_type', 'hydroponics_water')
            ->where('is_active', true)
            ->first();

        if (!$sensorSystem) {
            Log::warning("No active hydroponics_water sensor system found for device {$deviceId}");
            return collect([]);
        }

        // Get readings from the last N hours
        $startTime = Carbon::now()->subHours($hours);

        $readings = SensorReading::where('sensor_system_id', $sensorSystem->id)
            ->where('reading_time', '>=', $startTime)
            ->orderBy('reading_time', 'desc')
            ->get();

        return $readings;
    }

    /**
     * Evaluate sensor readings against target ranges
     *
     * @param Collection $readings
     * @param HydroponicSetup $setup
     * @return array
     */
    private function evaluateReadings(Collection $readings, HydroponicSetup $setup): array
    {
        $phMin = (float) $setup->target_ph_min;
        $phMax = (float) $setup->target_ph_max;
        $tdsMin = (float) $setup->target_tds_min;
        $tdsMax = (float) $setup->target_tds_max;

        // Count readings within range
        $phReadings = $readings->whereNotNull('ph');
        $tdsReadings = $readings->whereNotNull('tds');

        $phInRange = 0;
        $tdsInRange = 0;

        foreach ($phReadings as $reading) {
            $ph = (float) $reading->ph;
            if ($ph >= $phMin && $ph <= $phMax) {
                $phInRange++;
            }
        }

        foreach ($tdsReadings as $reading) {
            $tds = (float) $reading->tds;
            if ($tds >= $tdsMin && $tds <= $tdsMax) {
                $tdsInRange++;
            }
        }

        // Calculate percentages
        $phCount = $phReadings->count();
        $tdsCount = $tdsReadings->count();

        $phInRangePercent = $phCount > 0 ? round(($phInRange / $phCount) * 100, 1) : 0;
        $tdsInRangePercent = $tdsCount > 0 ? round(($tdsInRange / $tdsCount) * 100, 1) : 0;

        return [
            'ph_in_range_percent' => $phInRangePercent,
            'tds_in_range_percent' => $tdsInRangePercent,
            'ph_readings_count' => $phCount,
            'tds_readings_count' => $tdsCount,
            'total_readings' => $readings->count(),
        ];
    }

    /**
     * Determine health status based on percentage of readings within range
     *
     * @param float $phInRangePercent
     * @param float $tdsInRangePercent
     * @param int $readingCount
     * @return string
     */
    private function determineHealthStatus(
        float $phInRangePercent,
        float $tdsInRangePercent,
        int $readingCount
    ): string {
        // If insufficient readings, return moderate
        if ($readingCount < 10) {
            return 'moderate';
        }

        // Handle case where one parameter is missing
        if ($phInRangePercent === 0 && $tdsInRangePercent > 0) {
            // Only TDS available
            return $this->getStatusFromPercent($tdsInRangePercent);
        }

        if ($tdsInRangePercent === 0 && $phInRangePercent > 0) {
            // Only pH available
            return $this->getStatusFromPercent($phInRangePercent);
        }

        // Both parameters available - use the worse of the two
        $minPercent = min($phInRangePercent, $tdsInRangePercent);
        
        return $this->getStatusFromPercent($minPercent);
    }

    /**
     * Get health status from a single percentage value
     *
     * @param float $percent
     * @return string
     */
    private function getStatusFromPercent(float $percent): string
    {
        if ($percent >= 90) {
            return 'good';
        } elseif ($percent >= 60) {
            return 'moderate';
        } else {
            return 'poor';
        }
    }
}
