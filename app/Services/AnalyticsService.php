<?php

namespace App\Services;

use App\Models\HydroponicSetup;
use App\Models\SensorReading;
use App\Models\TreatmentReport;
use App\Models\TreatmentStage;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * YIELD CALCULATIONS
     */

    /**
     * Calculate yield efficiency (weight per day)
     */
    public function calculateYieldEfficiency(HydroponicSetup $setup): float
    {
        if (!$setup->harvest_date || !$setup->setup_date) {
            return 0.0;
        }

        $yield = $setup->hydroponic_yields->first();
        if (!$yield || !$yield->total_weight) {
            return 0.0;
        }

        $setupDate = Carbon::parse($setup->setup_date);
        $harvestDate = Carbon::parse($setup->harvest_date);
        $days = $setupDate->diffInDays($harvestDate);

        if ($days === 0) {
            return 0.0;
        }

        return round($yield->total_weight / $days, 2);
    }

    /**
     * Calculate grade distribution from yields collection
     */
    public function calculateGradeDistribution(Collection $yields): array
    {
        $totalSelling = 0;
        $totalConsumption = 0;
        $totalDisposal = 0;
        $totalCount = 0;

        foreach ($yields as $yield) {
            if ($yield->grades) {
                foreach ($yield->grades as $grade) {
                    $count = $grade->count ?? 0;
                    $totalCount += $count;

                    switch ($grade->grade) {
                        case 'selling':
                            $totalSelling += $count;
                            break;
                        case 'consumption':
                            $totalConsumption += $count;
                            break;
                        case 'disposal':
                            $totalDisposal += $count;
                            break;
                    }
                }
            }
        }

        $totalWeight = $yields->sum('total_weight');

        return [
            'selling' => [
                'count' => $totalSelling,
                'percentage' => $totalCount > 0 ? round(($totalSelling / $totalCount) * 100, 2) : 0,
            ],
            'consumption' => [
                'count' => $totalConsumption,
                'percentage' => $totalCount > 0 ? round(($totalConsumption / $totalCount) * 100, 2) : 0,
            ],
            'disposal' => [
                'count' => $totalDisposal,
                'percentage' => $totalCount > 0 ? round(($totalDisposal / $totalCount) * 100, 2) : 0,
            ],
            'total_count' => $totalCount,
            'total_weight' => round($totalWeight, 2),
        ];
    }

    /**
     * Calculate waste percentage from yields
     */
    public function calculateWastePercentage(Collection $yields): float
    {
        $distribution = $this->calculateGradeDistribution($yields);
        return $distribution['disposal']['percentage'];
    }

    /**
     * Calculate average yield per setup
     */
    public function calculateAverageYield(Collection $setups): array
    {
        $totalWeight = 0;
        $totalCount = 0;
        $setupsWithYield = 0;

        foreach ($setups as $setup) {
            $yield = $setup->hydroponic_yields->first();
            if ($yield) {
                $totalWeight += $yield->total_weight ?? 0;
                $totalCount += $yield->total_count ?? 0;
                $setupsWithYield++;
            }
        }

        return [
            'average_weight_per_setup' => $setupsWithYield > 0 ? round($totalWeight / $setupsWithYield, 2) : 0,
            'average_count_per_setup' => $setupsWithYield > 0 ? round($totalCount / $setupsWithYield, 2) : 0,
        ];
    }

    /**
     * WATER QUALITY CALCULATIONS
     */

    /**
     * Calculate parameter compliance percentage (readings within target range)
     */
    public function calculateParameterCompliance(
        Collection $readings,
        string $parameter,
        ?float $targetMin,
        ?float $targetMax
    ): float {
        if ($readings->isEmpty() || $targetMin === null || $targetMax === null) {
            return 0.0;
        }

        $totalReadings = $readings->count();
        $compliantReadings = $readings->filter(function ($reading) use ($parameter, $targetMin, $targetMax) {
            $value = $reading->$parameter;
            return $value !== null && $value >= $targetMin && $value <= $targetMax;
        })->count();

        return round(($compliantReadings / $totalReadings) * 100, 2);
    }

    /**
     * Detect trend direction for a parameter
     */
    public function detectTrend(Collection $readings, string $parameter): string
    {
        if ($readings->count() < 2) {
            return 'insufficient_data';
        }

        $values = $readings->pluck($parameter)->filter(function ($value) {
            return $value !== null;
        })->values();

        if ($values->count() < 2) {
            return 'insufficient_data';
        }

        // Simple linear regression to detect trend
        $n = $values->count();
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($values as $index => $value) {
            $x = $index;
            $y = $value;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        // Determine trend based on slope
        if (abs($slope) < 0.01) {
            return 'stable';
        } elseif ($slope > 0) {
            return 'improving';
        } else {
            return 'declining';
        }
    }

    /**
     * Calculate number of deviations (out-of-range readings)
     */
    public function calculateDeviations(
        Collection $readings,
        string $parameter,
        ?float $targetMin,
        ?float $targetMax
    ): int {
        if ($targetMin === null || $targetMax === null) {
            return 0;
        }

        return $readings->filter(function ($reading) use ($parameter, $targetMin, $targetMax) {
            $value = $reading->$parameter;
            return $value !== null && ($value < $targetMin || $value > $targetMax);
        })->count();
    }

    /**
     * Calculate statistical summary for readings
     */
    public function calculateStatistics(Collection $readings, string $parameter): array
    {
        $values = $readings->pluck($parameter)->filter(function ($value) {
            return $value !== null;
        })->values();

        if ($values->isEmpty()) {
            return [
                'min' => null,
                'max' => null,
                'average' => null,
                'median' => null,
            ];
        }

        $sorted = $values->sort()->values();
        $count = $sorted->count();
        $median = $count % 2 === 0
            ? ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2
            : $sorted[floor($count / 2)];

        return [
            'min' => round($sorted->min(), 2),
            'max' => round($sorted->max(), 2),
            'average' => round($values->avg(), 2),
            'median' => round($median, 2),
        ];
    }

    /**
     * TREATMENT CALCULATIONS
     */

    /**
     * Calculate treatment success rate
     */
    public function calculateTreatmentSuccessRate(Collection $reports): float
    {
        if ($reports->isEmpty()) {
            return 0.0;
        }

        $successfulReports = $reports->where('final_status', 'success')->count();
        return round(($successfulReports / $reports->count()) * 100, 2);
    }

    /**
     * Calculate stage-by-stage efficiency
     */
    public function calculateStageEfficiency(Collection $stages): array
    {
        $stageNames = ['MFC', 'Natural Filter', 'UV Filter', 'Clean Water Tank'];
        $efficiency = [];

        $orderedStages = $stages->sortBy('stage_order')->values();

        foreach ($stageNames as $stageName) {
            $stageData = $orderedStages->firstWhere('stage_name', $stageName);

            if ($stageData) {
                $efficiency[$stageName] = [
                    'pH' => $stageData->pH,
                    'turbidity' => $stageData->turbidity,
                    'TDS' => $stageData->TDS,
                    'status' => $stageData->status,
                ];
            }
        }

        // Calculate improvement percentages
        if (isset($efficiency['MFC']) && isset($efficiency['Clean Water Tank'])) {
            $firstStage = $efficiency['MFC'];
            $lastStage = $efficiency['Clean Water Tank'];

            $improvements = [
                'turbidity_reduction' => $this->calculateReductionPercentage(
                    $firstStage['turbidity'],
                    $lastStage['turbidity']
                ),
                'tds_reduction' => $this->calculateReductionPercentage(
                    $firstStage['TDS'],
                    $lastStage['TDS']
                ),
                'ph_change' => $this->calculatePhChange($firstStage['pH'], $lastStage['pH']),
            ];

            return [
                'stages' => $efficiency,
                'improvements' => $improvements,
            ];
        }

        return [
            'stages' => $efficiency,
            'improvements' => null,
        ];
    }

    /**
     * Calculate quality improvement between first and last stage
     */
    public function calculateQualityImprovement($firstStage, $lastStage): array
    {
        return [
            'turbidity_reduction' => $this->calculateReductionPercentage(
                $firstStage->turbidity,
                $lastStage->turbidity
            ),
            'tds_reduction' => $this->calculateReductionPercentage(
                $firstStage->TDS,
                $lastStage->TDS
            ),
            'ph_stabilization' => abs(($lastStage->pH ?? 7.0) - 7.0) < abs(($firstStage->pH ?? 7.0) - 7.0),
            'ph_change' => $this->calculatePhChange($firstStage->pH, $lastStage->pH),
        ];
    }

    /**
     * Calculate average treatment duration
     */
    public function calculateAverageDuration(Collection $reports): float
    {
        $durations = [];

        foreach ($reports as $report) {
            if ($report->start_time && $report->end_time) {
                $start = Carbon::parse($report->start_time);
                $end = Carbon::parse($report->end_time);
                $durations[] = $start->diffInMinutes($end);
            }
        }

        if (empty($durations)) {
            return 0.0;
        }

        return round(array_sum($durations) / count($durations), 2);
    }

    /**
     * Calculate water system comparison between dirty and clean water
     */
    public function calculateWaterSystemComparison(Collection $dirtyWaterReadings, Collection $cleanWaterReadings): array
    {
        if ($dirtyWaterReadings->isEmpty() || $cleanWaterReadings->isEmpty()) {
            return [
                'turbidity_reduction' => 0,
                'tds_reduction' => 0,
                'ph_stabilization' => false,
                'ph_change' => 0,
                'filtration_effectiveness' => 'insufficient_data',
            ];
        }

        // Calculate average values for dirty water
        $dirtyAvgTurbidity = $dirtyWaterReadings->avg('turbidity');
        $dirtyAvgTds = $dirtyWaterReadings->avg('tds');
        $dirtyAvgPh = $dirtyWaterReadings->avg('ph');

        // Calculate average values for clean water
        $cleanAvgTurbidity = $cleanWaterReadings->avg('turbidity');
        $cleanAvgTds = $cleanWaterReadings->avg('tds');
        $cleanAvgPh = $cleanWaterReadings->avg('ph');

        // Calculate improvement metrics
        $turbidityReduction = $this->calculateReductionPercentage($dirtyAvgTurbidity, $cleanAvgTurbidity);
        $tdsReduction = $this->calculateReductionPercentage($dirtyAvgTds, $cleanAvgTds);
        $phChange = $this->calculatePhChange($dirtyAvgPh, $cleanAvgPh);
        
        // pH stabilization: clean water pH closer to neutral (7.0) than dirty water
        $phStabilization = abs(($cleanAvgPh ?? 7.0) - 7.0) < abs(($dirtyAvgPh ?? 7.0) - 7.0);

        // Determine filtration effectiveness
        $effectiveness = $this->determineFiltrationEffectiveness($turbidityReduction, $tdsReduction);

        return [
            'turbidity_reduction' => $turbidityReduction,
            'tds_reduction' => $tdsReduction,
            'ph_stabilization' => $phStabilization,
            'ph_change' => $phChange,
            'filtration_effectiveness' => $effectiveness,
        ];
    }

    /**
     * Determine filtration effectiveness based on reduction percentages
     */
    private function determineFiltrationEffectiveness(float $turbidityReduction, float $tdsReduction): string
    {
        if ($turbidityReduction >= 90 && $tdsReduction >= 50) {
            return 'excellent';
        } elseif ($turbidityReduction >= 70 && $tdsReduction >= 30) {
            return 'good';
        } elseif ($turbidityReduction >= 50 || $tdsReduction >= 20) {
            return 'moderate';
        } else {
            return 'poor';
        }
    }

    /**
     * HELPER METHODS
     */

    /**
     * Calculate reduction percentage
     */
    private function calculateReductionPercentage(?float $initial, ?float $final): float
    {
        if ($initial === null || $final === null || $initial == 0) {
            return 0.0;
        }

        $reduction = (($initial - $final) / $initial) * 100;
        return round(max(0, $reduction), 2);
    }

    /**
     * Calculate pH change
     */
    private function calculatePhChange(?float $initialPh, ?float $finalPh): float
    {
        if ($initialPh === null || $finalPh === null) {
            return 0.0;
        }

        return round($finalPh - $initialPh, 2);
    }

    /**
     * Group readings by interval for time-series data
     */
    public function groupByInterval(Collection $readings, string $interval, string $dateField = 'reading_time'): Collection
    {
        return $readings->groupBy(function ($reading) use ($interval, $dateField) {
            $date = Carbon::parse($reading->$dateField);

            switch ($interval) {
                case 'hourly':
                    return $date->format('Y-m-d H:00');
                case 'daily':
                    return $date->format('Y-m-d');
                case 'weekly':
                    return $date->startOfWeek()->format('Y-m-d');
                default:
                    return $date->format('Y-m-d');
            }
        });
    }

    /**
     * Aggregate readings for a group
     */
    public function aggregateReadings(Collection $readings, string $parameter): array
    {
        $values = $readings->pluck($parameter)->filter(function ($value) {
            return $value !== null;
        });

        if ($values->isEmpty()) {
            return [
                'min' => null,
                'max' => null,
                'average' => null,
            ];
        }

        return [
            'min' => round($values->min(), 2),
            'max' => round($values->max(), 2),
            'average' => round($values->avg(), 2),
        ];
    }
}
