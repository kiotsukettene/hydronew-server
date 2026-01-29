<?php

namespace Database\Seeders;

use App\Models\HydroponicYield;
use App\Models\HydroponicYieldGrade;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HydroponicYieldGradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all hydroponic yields
        $yields = HydroponicYield::with('hydroponic_setup')->get();

        foreach ($yields as $yield) {
            $totalCount = $yield->total_count;
            $totalWeight = $yield->total_weight;
            
            // Determine grade distribution based on setup health status
            $healthStatus = $yield->hydroponic_setup->health_status ?? 'good';
            
            // Calculate distribution percentages
            [$sellingPercent, $consumptionPercent, $disposalPercent] = match ($healthStatus) {
                'good' => [0.75, 0.20, 0.05], // 75% selling, 20% consumption, 5% disposal
                'moderate' => [0.60, 0.25, 0.15], // 60% selling, 25% consumption, 15% disposal
                'poor' => [0.40, 0.30, 0.30], // 40% selling, 30% consumption, 30% disposal
                default => [0.70, 0.20, 0.10], // Default distribution
            };
            
            // Calculate counts for each grade
            $sellingCount = floor($totalCount * $sellingPercent);
            $consumptionCount = floor($totalCount * $consumptionPercent);
            $disposalCount = $totalCount - $sellingCount - $consumptionCount; // Remaining goes to disposal
            
            // Calculate weights for each grade (proportional to count)
            $sellingWeight = round(($totalWeight * $sellingPercent), 2);
            $consumptionWeight = round(($totalWeight * $consumptionPercent), 2);
            $disposalWeight = round(($totalWeight - $sellingWeight - $consumptionWeight), 2);
            
            // Create grade records
            if ($sellingCount > 0) {
                HydroponicYieldGrade::create([
                    'hydroponic_yield_id' => $yield->id,
                    'grade' => 'selling',
                    'count' => $sellingCount,
                    'weight' => $sellingWeight,
                ]);
            }
            
            if ($consumptionCount > 0) {
                HydroponicYieldGrade::create([
                    'hydroponic_yield_id' => $yield->id,
                    'grade' => 'consumption',
                    'count' => $consumptionCount,
                    'weight' => $consumptionWeight,
                ]);
            }
            
            if ($disposalCount > 0) {
                HydroponicYieldGrade::create([
                    'hydroponic_yield_id' => $yield->id,
                    'grade' => 'disposal',
                    'count' => $disposalCount,
                    'weight' => $disposalWeight,
                ]);
            }
        }
    }
}
