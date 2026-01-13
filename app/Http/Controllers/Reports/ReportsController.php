<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\CropComparisonRequest;
use App\Http\Requests\Reports\CropPerformanceRequest;
use App\Http\Requests\Reports\TreatmentReportRequest;
use App\Http\Requests\Reports\WaterQualityRequest;
use App\Models\Device;
use App\Models\HydroponicSetup;
use App\Models\SensorReading;
use App\Models\SensorSystem;
use App\Models\TreatmentReport;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get crop performance report
     */
    public function cropPerformance(CropPerformanceRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Build query for hydroponic setups
        $query = HydroponicSetup::where('user_id', $user->id)
            ->where('is_archived', false)
            ->where('status', '=', 'active');

        // Apply filters
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['crop_name'])) {
            $query->where('crop_name', 'like', '%' . $validated['crop_name'] . '%');
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('setup_date', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('setup_date', '<=', $validated['date_to']);
        }

        // Get setups
        $setups = $query->get();

        // Calculate growth stage distribution
        $growthStageDistribution = HydroponicSetup::where('user_id', $user->id)
            ->where('is_archived', false)
            ->whereNotNull('growth_stage')
            ->where('status', '=', 'active')
            ->select('growth_stage', DB::raw('count(*) as count'))
            ->groupBy('growth_stage')
            ->get()
            ->pluck('count', 'growth_stage');

        // Calculate health status distribution
        $healthStatusDistribution = HydroponicSetup::where('user_id', $user->id)
            ->where('is_archived', false)
            ->whereNotNull('health_status')
            ->where('status', '=', 'active')
            ->select('health_status', DB::raw('count(*) as count'))
            ->groupBy('health_status')
            ->get()
            ->pluck('count', 'health_status');

        // Calculate parameter compliance for active setups using latest sensor readings
        $parameterCompliance = [];
        $activeSetups = $setups->where('status', 'active');

        // Get user's hydroponics sensor system
        $sensorSystem = SensorSystem::whereHas('device', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('system_type', 'hydroponics_water')
            ->where('is_active', true)
            ->first();

        if ($sensorSystem) {
            // Get latest sensor reading
            $latestReading = $sensorSystem->latestReading;

            if ($latestReading) {
                foreach ($activeSetups as $setup) {
                    // Check pH compliance
                    if ($latestReading->ph !== null) {
                        $phInRange = $latestReading->ph >= $setup->target_ph_min &&
                            $latestReading->ph <= $setup->target_ph_max;

                        if (!isset($parameterCompliance['ph'])) {
                            $parameterCompliance['ph'] = ['compliant' => 0, 'total' => 0];
                        }
                        $parameterCompliance['ph']['total']++;
                        if ($phInRange) {
                            $parameterCompliance['ph']['compliant']++;
                        }
                    }

                    // Check TDS compliance
                    if ($latestReading->tds !== null) {
                        $tdsInRange = $latestReading->tds >= $setup->target_tds_min &&
                            $latestReading->tds <= $setup->target_tds_max;

                        if (!isset($parameterCompliance['tds'])) {
                            $parameterCompliance['tds'] = ['compliant' => 0, 'total' => 0];
                        }
                        $parameterCompliance['tds']['total']++;
                        if ($tdsInRange) {
                            $parameterCompliance['tds']['compliant']++;
                        }
                    }
                }
            }
        }

        // Calculate compliance percentages
        foreach ($parameterCompliance as $param => $data) {
            $parameterCompliance[$param]['percentage'] = $data['total'] > 0
                ? round(($data['compliant'] / $data['total']) * 100, 2)
                : 0;
        }

        // Get latest sensor reading for current parameters
        $latestReading = null;
        if ($sensorSystem) {
            $latestReading = $sensorSystem->latestReading;
        }

        // Format setup data
        $setupsData = $setups->map(function ($setup) use ($latestReading) {
            return [
                'id' => $setup->id,
                'crop_name' => $setup->crop_name,
                'number_of_crops' => $setup->number_of_crops,
                'bed_size' => $setup->bed_size,
                'status' => $setup->status,
                'setup_date' => $setup->setup_date,
                'harvest_date' => $setup->harvest_date,
                'health_status' => $setup->health_status ?? 'unknown',
                'growth_stage' => $setup->growth_stage ?? 'unknown',
                'current_parameters' => [
                    'ph' => $latestReading->ph ?? null,
                    'tds' => $latestReading->tds ?? null,
                    'ec' => $latestReading->ec ?? null,
                    'humidity' => $latestReading->humidity ?? null,
                ],
                'target_parameters' => [
                    'ph_min' => $setup->target_ph_min,
                    'ph_max' => $setup->target_ph_max,
                    'tds_min' => $setup->target_tds_min,
                    'tds_max' => $setup->target_tds_max,
                ],
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'setups' => $setupsData,
                'growth_stage_distribution' => $growthStageDistribution,
                'health_status_distribution' => $healthStatusDistribution,
                'parameter_compliance' => $parameterCompliance,
            ],
            'meta' => [
                'total_setups' => $setups->count(),
                'active_setups' => $activeSetups->count(),
                'filters_applied' => $validated,
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get yield summary report
     */
    public function yieldSummary(CropPerformanceRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Build query for harvested setups
        $query = HydroponicSetup::where('user_id', $user->id)
            ->where('harvest_status', 'harvested')
            ->with(['hydroponic_yields.grades']);

        // Apply filters
        if (!empty($validated['crop_name'])) {
            $query->where('crop_name', 'like', '%' . $validated['crop_name'] . '%');
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('harvest_date', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('harvest_date', '<=', $validated['date_to']);
        }

        $setups = $query->get();

        // Calculate total weight by crop type
        $weightByCrop = $setups->groupBy('crop_name')->map(function ($cropSetups) {
            return [
                'total_weight' => $cropSetups->sum(function ($setup) {
                    return $setup->hydroponic_yields->first()->total_weight ?? 0;
                }),
                'total_count' => $cropSetups->sum(function ($setup) {
                    return $setup->hydroponic_yields->first()->total_count ?? 0;
                }),
                'harvested_setups' => $cropSetups->count(),
            ];
        });

        // Calculate grade distribution
        $yields = $setups->flatMap(function ($setup) {
            return $setup->hydroponic_yields;
        });
        $gradeDistribution = $this->analyticsService->calculateGradeDistribution($yields);

        // Calculate average yield per setup
        $averageYield = $this->analyticsService->calculateAverageYield($setups);

        // Calculate sellable yield percentage
        $sellablePercentage = $gradeDistribution['selling']['percentage'];

        // Calculate waste percentage
        $wastePercentage = $gradeDistribution['disposal']['percentage'];

        // Calculate consumption percentage
        $consumptionPercentage = $gradeDistribution['consumption']['percentage'];

        // Month-over-month comparison (if applicable)
        $monthOverMonth = null;
        if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
            $previousPeriodStart = Carbon::parse($validated['date_from'])->subMonth();
            $previousPeriodEnd = Carbon::parse($validated['date_to'])->subMonth();

            $previousSetups = HydroponicSetup::where('user_id', $user->id)
                ->where('harvest_status', 'harvested')
                ->whereDate('harvest_date', '>=', $previousPeriodStart)
                ->whereDate('harvest_date', '<=', $previousPeriodEnd)
                ->with(['hydroponic_yields.grades'])
                ->get();

            if ($previousSetups->isNotEmpty()) {
                $previousYields = $previousSetups->flatMap(function ($setup) {
                    return $setup->hydroponic_yields;
                });

                $currentTotalWeight = $gradeDistribution['total_weight'];
                $previousTotalWeight = $previousYields->sum('total_weight');

                $monthOverMonth = [
                    'current_period' => [
                        'total_weight' => $currentTotalWeight,
                        'total_setups' => $setups->count(),
                    ],
                    'previous_period' => [
                        'total_weight' => round($previousTotalWeight, 2),
                        'total_setups' => $previousSetups->count(),
                    ],
                    'change_percentage' => $previousTotalWeight > 0
                        ? round((($currentTotalWeight - $previousTotalWeight) / $previousTotalWeight) * 100, 2)
                        : 0,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_harvested_setups' => $setups->count(),
                'weight_by_crop' => $weightByCrop,
                'grade_distribution' => $gradeDistribution,
                'average_yield' => $averageYield,
                'sellable_yield_percentage' => $sellablePercentage,
                'waste_percentage' => $wastePercentage,
                'consumption_percentage' => $consumptionPercentage,
                'month_over_month' => $monthOverMonth,
            ],
            'meta' => [
                'date_range' => [
                    'from' => $validated['date_from'] ?? null,
                    'to' => $validated['date_to'] ?? null,
                ],
                'filters_applied' => $validated,
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get crop comparison report
     */
    public function cropComparison(CropComparisonRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $cropNames = $validated['crop_names'];
        $metric = $validated['metric'] ?? 'weight';

        $comparisons = [];

        foreach ($cropNames as $cropName) {
            $setups = HydroponicSetup::where('user_id', $user->id)
                ->where('crop_name', $cropName)
                ->where('harvest_status', 'harvested')
                ->with(['hydroponic_yields.grades'])
                ->get();

            if ($setups->isEmpty()) {
                $comparisons[$cropName] = [
                    'total_setups' => 0,
                    'harvested_setups' => 0,
                    'success_rate' => 0,
                    'average_weight' => 0,
                    'average_duration' => 0,
                    'yield_efficiency' => 0,
                    'quality_distribution' => null,
                ];
                continue;
            }

            // Calculate metrics
            $totalWeight = $setups->sum(function ($setup) {
                return $setup->hydroponic_yields->first()->total_weight ?? 0;
            });

            $totalDuration = 0;
            $setupsWithDuration = 0;

            foreach ($setups as $setup) {
                if ($setup->setup_date && $setup->harvest_date) {
                    $duration = Carbon::parse($setup->setup_date)->diffInDays(Carbon::parse($setup->harvest_date));
                    $totalDuration += $duration;
                    $setupsWithDuration++;
                }
            }

            $averageDuration = $setupsWithDuration > 0 ? $totalDuration / $setupsWithDuration : 0;
            $averageWeight = $setups->count() > 0 ? $totalWeight / $setups->count() : 0;
            $yieldEfficiency = $averageDuration > 0 ? $averageWeight / $averageDuration : 0;

            // Calculate grade distribution
            $yields = $setups->flatMap(function ($setup) {
                return $setup->hydroponic_yields;
            });
            $gradeDistribution = $this->analyticsService->calculateGradeDistribution($yields);

            // Calculate success rate (total setups vs harvested)
            $totalSetupsForCrop = HydroponicSetup::where('user_id', $user->id)
                ->where('crop_name', $cropName)
                ->count();

            $successRate = $totalSetupsForCrop > 0
                ? round(($setups->count() / $totalSetupsForCrop) * 100, 2)
                : 0;

            $comparisons[$cropName] = [
                'total_setups' => $totalSetupsForCrop,
                'harvested_setups' => $setups->count(),
                'success_rate' => $successRate,
                'average_weight' => round($averageWeight, 2),
                'average_duration' => round($averageDuration, 2),
                'yield_efficiency' => round($yieldEfficiency, 2),
                'quality_distribution' => [
                    'selling_percentage' => $gradeDistribution['selling']['percentage'],
                    'consumption_percentage' => $gradeDistribution['consumption']['percentage'],
                    'disposal_percentage' => $gradeDistribution['disposal']['percentage'],
                ],
            ];
        }

        // Determine best performing crop based on metric
        $bestCrop = null;
        $bestValue = 0;

        foreach ($comparisons as $cropName => $data) {
            $value = match ($metric) {
                'weight' => $data['average_weight'],
                'duration' => -$data['average_duration'], // Lower is better, so negate
                'quality' => $data['quality_distribution']['selling_percentage'] ?? 0,
                default => $data['average_weight'],
            };

            if ($value > $bestValue) {
                $bestValue = $value;
                $bestCrop = $cropName;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'comparisons' => $comparisons,
                'best_performing_crop' => [
                    'crop_name' => $bestCrop,
                    'metric' => $metric,
                ],
            ],
            'meta' => [
                'crops_compared' => count($cropNames),
                'metric_used' => $metric,
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get water quality historical data
     */
    public function waterQualityHistorical(WaterQualityRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $systemType = $validated['system_type'] ?? 'dirty_water';
        $interval = $validated['interval'] ?? 'daily';
        $dateFrom = $validated['date_from'] ?? Carbon::now()->subDays(7)->toDateString();
        $dateTo = $validated['date_to'] ?? Carbon::now()->toDateString();

        // Get user's devices
        $deviceIds = Device::where('user_id', $user->id)->pluck('id');

        // Get sensor system
        $sensorSystem = SensorSystem::whereIn('device_id', $deviceIds)
            ->where('system_type', $systemType)
            ->where('is_active', true)
            ->first();

        if (!$sensorSystem) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active sensor system found for the specified type.',
            ], 404);
        }

        // Get readings within date range
        $readings = SensorReading::where('sensor_system_id', $sensorSystem->id)
            ->whereBetween('reading_time', [$dateFrom, $dateTo])
            ->orderBy('reading_time', 'asc')
            ->get();

        if ($readings->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'time_series' => [],
                    'statistics' => null,
                ],
                'meta' => [
                    'system_type' => $systemType,
                    'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                    'interval' => $interval,
                    'total_readings' => 0,
                    'generated_at' => Carbon::now()->toIso8601String(),
                ],
            ]);
        }

        // Group readings by interval
        $groupedReadings = $this->analyticsService->groupByInterval($readings, $interval);

        // Prepare time series data for each parameter (all columns from sensor_readings table)
        $parameters = ['ph', 'tds', 'ec', 'turbidity', 'temperature', 'humidity', 'water_level', 'electric_current'];
        $timeSeries = [];

        foreach ($groupedReadings as $timestamp => $groupReadings) {
            $dataPoint = ['timestamp' => $timestamp];

            foreach ($parameters as $parameter) {
                $aggregated = $this->analyticsService->aggregateReadings($groupReadings, $parameter);
                $dataPoint[$parameter] = $aggregated;
            }

            $timeSeries[] = $dataPoint;
        }

        // Calculate overall statistics for each parameter
        $statistics = [];
        foreach ($parameters as $parameter) {
            $statistics[$parameter] = $this->analyticsService->calculateStatistics($readings, $parameter);
        }

        // Calculate out-of-range occurrences (for hydroponics system)
        $outOfRangeCount = [];
        if ($systemType === 'hydroponics_water') {
            // Get target ranges from active hydroponic setups
            $activeSetup = HydroponicSetup::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($activeSetup) {
                $outOfRangeCount['ph'] = $this->analyticsService->calculateDeviations(
                    $readings,
                    'ph',
                    $activeSetup->target_ph_min,
                    $activeSetup->target_ph_max
                );

                $outOfRangeCount['tds'] = $this->analyticsService->calculateDeviations(
                    $readings,
                    'tds',
                    $activeSetup->target_tds_min,
                    $activeSetup->target_tds_max
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'time_series' => $timeSeries,
                'statistics' => $statistics,
                'out_of_range_count' => $outOfRangeCount,
            ],
            'meta' => [
                'system_type' => $systemType,
                'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                'interval' => $interval,
                'total_readings' => $readings->count(),
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get water quality trends
     */
    public function waterQualityTrends(WaterQualityRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $systemType = $validated['system_type'] ?? 'dirty_water';
        $days = $validated['days'] ?? 7;

        // Determine which parameters to track based on system type
        $parameters = match ($systemType) {
            'dirty_water' => ['ph', 'turbidity', 'tds'],
            'clean_water' => ['ph', 'turbidity', 'tds'],
            'hydroponics_water' => ['ph', 'tds', 'ec', 'humidity'],
            default => ['ph', 'turbidity', 'tds'],
        };

        // Get user's devices
        $deviceIds = Device::where('user_id', $user->id)->pluck('id');

        // Get sensor system
        $sensorSystem = SensorSystem::whereIn('device_id', $deviceIds)
            ->where('system_type', $systemType)
            ->where('is_active', true)
            ->first();

        if (!$sensorSystem) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active sensor system found for the specified type.',
            ], 404);
        }

        // Get readings for the specified days
        $dateFrom = Carbon::now()->subDays($days)->startOfDay();
        $readings = SensorReading::where('sensor_system_id', $sensorSystem->id)
            ->where('reading_time', '>=', $dateFrom)
            ->orderBy('reading_time', 'asc')
            ->get();

        if ($readings->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'labels' => [],
                    'datasets' => [],
                    'statistics' => null,
                    'trends' => [],
                    'recommendations' => [],
                ],
                'meta' => [
                    'system_type' => $systemType,
                    'parameters' => $parameters,
                    'days' => $days,
                    'generated_at' => Carbon::now()->toIso8601String(),
                ],
            ]);
        }

        // Group by day for chart data
        $dailyReadings = $readings->groupBy(function ($reading) {
            return Carbon::parse($reading->reading_time)->format('Y-m-d');
        });

        // Prepare labels (dates)
        $labels = $dailyReadings->keys()->toArray();

        // Get target ranges (for hydroponics)
        $targetRanges = [];
        if ($systemType === 'hydroponics_water') {
            $activeSetup = HydroponicSetup::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($activeSetup) {
                $targetRanges['ph'] = [
                    'min' => $activeSetup->target_ph_min,
                    'max' => $activeSetup->target_ph_max,
                ];
                $targetRanges['tds'] = [
                    'min' => $activeSetup->target_tds_min,
                    'max' => $activeSetup->target_tds_max,
                ];
            }
        }

        // Prepare datasets for each parameter
        $datasets = [];
        $statistics = [];
        $trends = [];
        $recommendations = [];

        foreach ($parameters as $parameter) {
            // Prepare daily data for this parameter
            $data = [];
            foreach ($dailyReadings as $date => $dayReadings) {
                $values = $dayReadings->pluck($parameter)->filter(function ($value) {
                    return $value !== null;
                });
                $data[] = $values->isNotEmpty() ? round($values->avg(), 2) : null;
            }

            // Calculate statistics
            $paramStats = $this->analyticsService->calculateStatistics($readings, $parameter);
            $statistics[$parameter] = $paramStats;

            // Detect trend
            $trend = $this->analyticsService->detectTrend($readings, $parameter);
            $trends[$parameter] = $trend;

            // Calculate deviations (if target ranges exist)
            $deviationCount = 0;
            if (isset($targetRanges[$parameter])) {
                $deviationCount = $this->analyticsService->calculateDeviations(
                    $readings,
                    $parameter,
                    $targetRanges[$parameter]['min'],
                    $targetRanges[$parameter]['max']
                );
            }

            // Prepare dataset for this parameter
            $datasets[$parameter] = [
                'label' => strtoupper($parameter) . ' Level',
                'data' => $data,
                'target_min' => $targetRanges[$parameter]['min'] ?? null,
                'target_max' => $targetRanges[$parameter]['max'] ?? null,
                'unit' => $this->getParameterUnit($parameter),
                'current_reading' => $readings->last()->$parameter ?? null,
                'historical_average' => $paramStats['average'],
                'deviation_count' => $deviationCount,
            ];

            // Generate recommendation based on trend and system type
            $recommendation = $this->generateRecommendation($systemType, $parameter, $trend, $deviationCount, $readings->count());
            if ($recommendation) {
                $recommendations[] = $recommendation;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'labels' => $labels,
                'datasets' => $datasets,
                'statistics' => $statistics,
                'trends' => $trends,
                'recommendations' => $recommendations,
            ],
            'meta' => [
                'system_type' => $systemType,
                'parameters' => $parameters,
                'days' => $days,
                'total_readings' => $readings->count(),
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get parameter unit
     */
    private function getParameterUnit(string $parameter): string
    {
        return match ($parameter) {
            'ph' => 'pH',
            'tds' => 'ppm',
            'ec' => 'mS/cm',
            'turbidity' => 'NTU',
            'temperature' => '°C',
            'humidity' => '%',
            'water_level' => 'cm',
            'electric_current' => 'A',
            default => '',
        };
    }

    /**
     * Generate recommendation based on system type, parameter, and trend
     */
    private function generateRecommendation(
        string $systemType,
        string $parameter,
        string $trend,
        int $deviationCount,
        int $totalReadings
    ): ?string {
        // Calculate deviation percentage
        $deviationPercentage = $totalReadings > 0 ? ($deviationCount / $totalReadings) * 100 : 0;

        // Recommendations for dirty_water and clean_water systems
        if (in_array($systemType, ['dirty_water', 'clean_water'])) {
            if ($parameter === 'turbidity') {
                if ($trend === 'improving') {
                    return 'Turbidity levels are improving. Water treatment is effective.';
                } elseif ($trend === 'declining') {
                    return 'Turbidity levels are increasing. Check filtration system and consider maintenance.';
                }
            } elseif ($parameter === 'ph') {
                if ($trend === 'declining') {
                    return 'pH levels are declining (becoming more acidic). Monitor water source and treatment process.';
                } elseif ($trend === 'improving') {
                    return 'pH levels are stabilizing. Continue current treatment protocols.';
                }
            } elseif ($parameter === 'tds') {
                if ($trend === 'improving') {
                    return 'TDS levels are decreasing. Water purification is working effectively.';
                } elseif ($trend === 'declining') {
                    return 'TDS levels are increasing. Check filtration stages and cleaning cycles.';
                }
            }
        }

        // Recommendations for hydroponics_water system
        if ($systemType === 'hydroponics_water') {
            if ($deviationPercentage > 30) {
                return "Over 30% of {$parameter} readings are out of target range. Immediate adjustment recommended.";
            }

            if ($parameter === 'ph') {
                if ($trend === 'declining') {
                    return 'pH levels are declining. Consider checking nutrient solution and adjusting pH levels.';
                } elseif ($trend === 'improving') {
                    return 'pH levels are improving and moving toward optimal range.';
                }
            } elseif ($parameter === 'tds') {
                if ($trend === 'declining') {
                    return 'TDS levels are declining. Plants may need additional nutrients.';
                } elseif ($trend === 'improving') {
                    return 'TDS levels are increasing. Monitor to ensure levels stay within target range.';
                }
            } elseif ($parameter === 'ec') {
                if ($trend === 'declining') {
                    return 'EC levels are declining. Check nutrient concentration in solution.';
                } elseif ($trend === 'improving') {
                    return 'EC levels are stable. Nutrient solution is properly balanced.';
                }
            } elseif ($parameter === 'humidity') {
                if ($trend === 'declining') {
                    return 'Humidity levels are declining. Consider increasing misting or ventilation control.';
                } elseif ($trend === 'improving') {
                    return 'Humidity levels are rising. Ensure proper ventilation to prevent mold growth.';
                }
            }
        }

        return null;
    }

    /**
     * Get treatment performance report
     */
    public function treatmentPerformance(TreatmentReportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $deviceId = $validated['device_id'];
        $dateFrom = $validated['date_from'] ?? Carbon::now()->subDays(30)->toDateString();
        $dateTo = $validated['date_to'] ?? Carbon::now()->toDateString();

        // Get treatment reports within date range
        $reports = TreatmentReport::where('device_id', $deviceId)
            ->whereBetween('start_time', [$dateFrom, $dateTo])
            ->with('treatment_stages')
            ->get();

        if ($reports->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_cycles' => 0,
                    'success_rate' => 0,
                    'failure_rate' => 0,
                    'average_duration' => 0,
                    'stage_efficiency' => null,
                    'failure_analysis' => null,
                ],
                'meta' => [
                    'device_id' => $deviceId,
                    'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                    'generated_at' => Carbon::now()->toIso8601String(),
                ],
            ]);
        }

        // Calculate success and failure rates
        $successRate = $this->analyticsService->calculateTreatmentSuccessRate($reports);
        $failureRate = 100 - $successRate;

        // Calculate average duration
        $averageDuration = $this->analyticsService->calculateAverageDuration($reports);

        // Analyze stage-by-stage efficiency
        $allStages = $reports->flatMap(function ($report) {
            return $report->treatment_stages;
        });

        // Group stages by treatment to calculate average improvements
        $stageEfficiencyData = [];
        $stageNames = ['MFC', 'Natural Filter', 'UV Filter', 'Clean Water Tank'];

        foreach ($stageNames as $stageName) {
            $stageReadings = $allStages->where('stage_name', $stageName);

            if ($stageReadings->isNotEmpty()) {
                $stageEfficiencyData[$stageName] = [
                    'average_ph' => round($stageReadings->avg('pH'), 2),
                    'average_turbidity' => round($stageReadings->avg('turbidity'), 2),
                    'average_tds' => round($stageReadings->avg('TDS'), 2),
                    'passed_count' => $stageReadings->where('status', 'passed')->count(),
                    'failed_count' => $stageReadings->where('status', 'failed')->count(),
                ];
            }
        }

        // Calculate overall improvements (first stage to last stage)
        $improvements = [];
        foreach ($reports as $report) {
            $stages = $report->treatment_stages->sortBy('stage_order');
            if ($stages->count() >= 2) {
                $firstStage = $stages->first();
                $lastStage = $stages->last();

                $improvement = $this->analyticsService->calculateQualityImprovement($firstStage, $lastStage);
                $improvements[] = $improvement;
            }
        }

        $averageImprovements = null;
        if (!empty($improvements)) {
            $averageImprovements = [
                'turbidity_reduction' => round(collect($improvements)->avg('turbidity_reduction'), 2),
                'tds_reduction' => round(collect($improvements)->avg('tds_reduction'), 2),
                'ph_change' => round(collect($improvements)->avg('ph_change'), 2),
            ];
        }

        // Failure analysis
        $failedReports = $reports->where('final_status', 'failed');
        $failureAnalysis = null;

        if ($failedReports->isNotEmpty()) {
            $failedStages = $failedReports->flatMap(function ($report) {
                return $report->treatment_stages->where('status', 'failed');
            });

            $mostCommonFailureStage = $failedStages->groupBy('stage_name')
                ->map(function ($stages) {
                    return $stages->count();
                })
                ->sortDesc()
                ->keys()
                ->first();

            $failureAnalysis = [
                'total_failures' => $failedReports->count(),
                'most_common_failure_stage' => $mostCommonFailureStage,
                'failure_reasons' => $failedStages->pluck('notes')->filter()->values(),
            ];
        }

        // Calculate performance score (0-100)
        $performanceScore = round(
            ($successRate * 0.7) + // 70% weight on success rate
            (min(100, ($averageImprovements['turbidity_reduction'] ?? 0)) * 0.3), // 30% on quality improvement
            2
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_cycles' => $reports->count(),
                'success_rate' => $successRate,
                'failure_rate' => $failureRate,
                'average_duration_minutes' => $averageDuration,
                'stage_efficiency' => $stageEfficiencyData,
                'average_improvements' => $averageImprovements,
                'failure_analysis' => $failureAnalysis,
                'performance_score' => $performanceScore,
            ],
            'meta' => [
                'device_id' => $deviceId,
                'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                'total_reports' => $reports->count(),
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get treatment efficiency report
     */
    public function treatmentEfficiency(TreatmentReportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $deviceId = $validated['device_id'];
        $days = $validated['days'] ?? 30;

        $dateFrom = Carbon::now()->subDays($days)->startOfDay();

        // Get treatment reports
        $reports = TreatmentReport::where('device_id', $deviceId)
            ->where('start_time', '>=', $dateFrom)
            ->with('treatment_stages')
            ->orderBy('start_time', 'asc')
            ->get();

        if ($reports->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'water_quality_improvements' => null,
                    'cycle_trends' => [],
                    'success_rate_trend' => [],
                    'efficiency_score_trend' => 'stable',
                    'maintenance_recommendation' => null,
                ],
                'meta' => [
                    'device_id' => $deviceId,
                    'days' => $days,
                    'generated_at' => Carbon::now()->toIso8601String(),
                ],
            ]);
        }

        // Calculate water quality improvements
        $improvements = [];
        foreach ($reports->where('final_status', 'success') as $report) {
            $stages = $report->treatment_stages->sortBy('stage_order');
            if ($stages->count() >= 2) {
                $firstStage = $stages->first();
                $lastStage = $stages->last();
                $improvement = $this->analyticsService->calculateQualityImprovement($firstStage, $lastStage);
                $improvements[] = $improvement;
            }
        }

        $waterQualityImprovements = null;
        if (!empty($improvements)) {
            $waterQualityImprovements = [
                'average_turbidity_reduction' => round(collect($improvements)->avg('turbidity_reduction'), 2),
                'average_tds_reduction' => round(collect($improvements)->avg('tds_reduction'), 2),
                'ph_stabilization_rate' => round(
                    (collect($improvements)->where('ph_stabilization', true)->count() / count($improvements)) * 100,
                    2
                ),
            ];
        }

        // Calculate daily cycle counts and success rates
        $dailyData = $reports->groupBy(function ($report) {
            return Carbon::parse($report->start_time)->format('Y-m-d');
        });

        $cycleTrends = [];
        $successRateTrend = [];

        foreach ($dailyData as $date => $dayReports) {
            $cycleTrends[] = [
                'date' => $date,
                'cycle_count' => $dayReports->count(),
            ];

            $successRate = $dayReports->where('final_status', 'success')->count() / $dayReports->count() * 100;
            $successRateTrend[] = [
                'date' => $date,
                'success_rate' => round($successRate, 2),
            ];
        }

        // Calculate average cycles per day
        $averageCyclesPerDay = round($reports->count() / $days, 2);

        // Analyze efficiency score trend
        $recentReports = $reports->sortByDesc('start_time')->take(10);
        $olderReports = $reports->sortByDesc('start_time')->skip(10)->take(10);

        $recentSuccessRate = $recentReports->isNotEmpty()
            ? $this->analyticsService->calculateTreatmentSuccessRate($recentReports)
            : 0;

        $olderSuccessRate = $olderReports->isNotEmpty()
            ? $this->analyticsService->calculateTreatmentSuccessRate($olderReports)
            : 0;

        $efficiencyTrend = 'stable';
        if ($recentSuccessRate > $olderSuccessRate + 5) {
            $efficiencyTrend = 'improving';
        } elseif ($recentSuccessRate < $olderSuccessRate - 5) {
            $efficiencyTrend = 'declining';
        }

        // Generate maintenance recommendation
        $maintenanceRecommendation = null;
        if ($efficiencyTrend === 'declining') {
            $maintenanceRecommendation = 'System efficiency is declining. Consider scheduling maintenance to check filters and cleaning cycles.';
        } elseif ($recentSuccessRate < 80) {
            $maintenanceRecommendation = 'Success rate below 80%. Inspect treatment stages for potential issues.';
        } elseif ($averageCyclesPerDay < 1) {
            $maintenanceRecommendation = 'Low cycle frequency detected. Verify system is operating as expected.';
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'water_quality_improvements' => $waterQualityImprovements,
                'cycle_trends' => $cycleTrends,
                'success_rate_trend' => $successRateTrend,
                'average_cycles_per_day' => $averageCyclesPerDay,
                'efficiency_score_trend' => $efficiencyTrend,
                'recent_success_rate' => $recentSuccessRate,
                'maintenance_recommendation' => $maintenanceRecommendation,
            ],
            'meta' => [
                'device_id' => $deviceId,
                'days' => $days,
                'total_cycles' => $reports->count(),
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }
}
