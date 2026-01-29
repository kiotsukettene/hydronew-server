<?php

namespace Database\Seeders;

use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HydroponicYieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all hydroponic setups
        $setups = HydroponicSetup::all();

        foreach ($setups as $setup) {
            // Only create yields for harvested or harvest-ready setups
            if ($setup->harvest_status === 'harvested' || $setup->growth_stage === 'harvest-ready') {
                
                // Calculate realistic yield based on number of crops and crop type
                $baseYieldPerCrop = match ($setup->crop_name) {
                    'olmetie' => rand(80, 120), // grams per plant
                    'loose-leaf' => rand(100, 150),
                    'green-rapid' => rand(60, 90),
                    'romaine' => rand(120, 180),
                    'butterhead' => rand(90, 140),
                    default => rand(80, 120),
                };

                // Calculate total count (some crops might not make it to harvest)
                $harvestSuccessRate = match ($setup->health_status) {
                    'good' => rand(90, 100) / 100,
                    'moderate' => rand(75, 89) / 100,
                    'poor' => rand(60, 74) / 100,
                    default => rand(85, 95) / 100,
                };

                $totalCount = floor($setup->number_of_crops * $harvestSuccessRate);
                $totalWeight = $totalCount * $baseYieldPerCrop + rand(-500, 500); // Add some variation

                // Generate appropriate notes based on health status and crop
                $notes = match (true) {
                    $setup->health_status === 'good' => "Excellent harvest. {$setup->crop_name} showed strong growth with minimal losses.",
                    $setup->health_status === 'moderate' => "Good harvest overall. Some {$setup->crop_name} showed signs of nutrient stress.",
                    $setup->health_status === 'poor' => "Harvest completed with challenges. {$setup->crop_name} affected by environmental factors.",
                    $setup->growth_stage === 'overgrown' => "Harvest delayed. Some {$setup->crop_name} plants exceeded optimal harvest window.",
                    default => "Standard harvest completed for {$setup->crop_name}.",
                };

                HydroponicYield::create([
                    'hydroponic_setup_id' => $setup->id,
                    'total_weight' => round($totalWeight, 2),
                    'total_count' => $totalCount,
                    'notes' => $notes,
                    'is_archived' => $setup->is_archived ?? false,
                ]);
            }
        }
    }
}
