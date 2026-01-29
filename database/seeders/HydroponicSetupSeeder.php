<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\HydroponicSetup;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HydroponicSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $device = Device::first();

        if (!$user || !$device) {
            return; // Safety check
        }

        // Growth stage calculation logic:
        // - seedling: plantAge < 14 days
        // - vegetative: 14 <= plantAge < 30 days
        // - flowering: plantAge >= 30 days (and harvest date is still future)
        // - harvest-ready: harvest date has passed (but within 5 days)
        // - overgrown: more than 5 days past harvest date

        $setups = [
            [
                'crop_name' => 'olmetie',
                'number_of_crops' => 50,
                'bed_size' => 'medium',
                'pump_config' => ['pump1' => 'on', 'pump2' => 'off'],
                'nutrient_solution' => 'NPK 20-20-20',
                'target_ph_min' => 5.5,
                'target_ph_max' => 6.5,
                'target_tds_min' => 400,
                'target_tds_max' => 800,
                'water_amount' => '20L',
                'harvest_date' => now()->addDays(35),
                'setup_date' => now()->subDays(10), // 10 days old = seedling
                'growth_stage' => 'seedling',
            ],
            [
                'crop_name' => 'loose-leaf',
                'number_of_crops' => 80,
                'bed_size' => 'large',
                'pump_config' => ['pump1' => 'on', 'pump2' => 'on'],
                'nutrient_solution' => 'NPK 15-15-15',
                'target_ph_min' => 5.8,
                'target_ph_max' => 6.2,
                'target_tds_min' => 500,
                'target_tds_max' => 900,
                'water_amount' => '30L',
                'harvest_date' => now()->addDays(20),
                'setup_date' => now()->subDays(20), // 20 days old = vegetative (14-29)
                'growth_stage' => 'vegetative',
            ],
            [
                'crop_name' => 'green-rapid',
                'number_of_crops' => 40,
                'bed_size' => 'medium',
                'pump_config' => ['pump1' => 'on'],
                'nutrient_solution' => 'Herb Mix A',
                'target_ph_min' => 5.5,
                'target_ph_max' => 6.5,
                'target_tds_min' => 700,
                'target_tds_max' => 1200,
                'water_amount' => '18L',
                'harvest_date' => now()->addDays(15),
                'setup_date' => now()->subDays(25), // 25 days old = vegetative (14-29)
                'growth_stage' => 'vegetative',
            ],
            [
                'crop_name' => 'romaine',
                'number_of_crops' => 60,
                'bed_size' => 'large',
                'pump_config' => ['pump1' => 'on', 'pump2' => 'off'],
                'nutrient_solution' => 'Leafy Green Mix',
                'target_ph_min' => 6.0,
                'target_ph_max' => 7.0,
                'target_tds_min' => 600,
                'target_tds_max' => 1000,
                'water_amount' => '28L',
                'harvest_date' => now()->addDays(10),
                'setup_date' => now()->subDays(35), // 35 days old, harvest in future = flowering
                'growth_stage' => 'flowering',
                'health_status' => 'moderate',
            ],
            [
                'crop_name' => 'butterhead',
                'number_of_crops' => 35,
                'bed_size' => 'medium',
                'pump_config' => ['pump1' => 'on'],
                'nutrient_solution' => 'Cal-Mag Blend',
                'target_ph_min' => 5.5,
                'target_ph_max' => 6.5,
                'target_tds_min' => 800,
                'target_tds_max' => 1400,
                'water_amount' => '15L',
                'harvest_date' => now()->addDays(45),
                'setup_date' => now()->subDays(8), // 8 days old = seedling (<14)
                'growth_stage' => 'seedling',
            ],
            [
                'crop_name' => 'butterhead',
                'number_of_crops' => 25,
                'bed_size' => 'small',
                'pump_config' => ['pump1' => 'off'],
                'nutrient_solution' => 'Herb Nutrient B',
                'target_ph_min' => 6.0,
                'target_ph_max' => 7.0,
                'target_tds_min' => 500,
                'target_tds_max' => 900,
                'water_amount' => '10L',
                'harvest_date' => now()->subDays(2), // Past harvest date = harvest-ready
                'setup_date' => now()->subDays(45),
                'growth_stage' => 'harvest-ready',
                'harvest_status' => 'harvested',
                'status' => 'inactive',
                'is_archived' => true,
            ],
            [
                'crop_name' => 'loose-leaf',
                'number_of_crops' => 20,
                'bed_size' => 'large',
                'pump_config' => ['pump1' => 'on', 'pump2' => 'on'],
                'nutrient_solution' => 'Tomato Bloom Mix',
                'target_ph_min' => 5.8,
                'target_ph_max' => 6.3,
                'target_tds_min' => 1200,
                'target_tds_max' => 2000,
                'water_amount' => '40L',
                'harvest_date' => now()->subDays(1), // 1 day past harvest = harvest-ready (within 5 days)
                'setup_date' => now()->subDays(50),
                'growth_stage' => 'harvest-ready',
            ],
            [
                'crop_name' => 'olmetie',
                'number_of_crops' => 18,
                'bed_size' => 'large',
                'pump_config' => ['pump1' => 'on'],
                'nutrient_solution' => 'Fruit Crop Mix',
                'target_ph_min' => 5.5,
                'target_ph_max' => 6.0,
                'target_tds_min' => 1000,
                'target_tds_max' => 1800,
                'water_amount' => '35L',
                'harvest_date' => now()->subDays(7), // 7 days past harvest = overgrown (>5 days)
                'setup_date' => now()->subDays(55),
                'growth_stage' => 'overgrown',
                'health_status' => 'moderate',
            ],
            [
                'crop_name' => 'romaine',
                'number_of_crops' => 70,
                'bed_size' => 'medium',
                'pump_config' => ['pump1' => 'on'],
                'nutrient_solution' => 'Leafy Nutrient Pro',
                'target_ph_min' => 6.0,
                'target_ph_max' => 7.0,
                'target_tds_min' => 600,
                'target_tds_max' => 1100,
                'water_amount' => '22L',
                'harvest_date' => now()->addDays(20),
                'setup_date' => now()->subDays(18), // 18 days old = vegetative (14-29)
                'growth_stage' => 'vegetative',
            ],
            [
                'crop_name' => 'butterhead',
                'number_of_crops' => 55,
                'bed_size' => 'medium',
                'pump_config' => ['pump1' => 'on'],
                'nutrient_solution' => 'Rapid Grow Mix',
                'target_ph_min' => 6.0,
                'target_ph_max' => 6.8,
                'target_tds_min' => 500,
                'target_tds_max' => 900,
                'water_amount' => '20L',
                'harvest_date' => now()->addDays(0), // Harvest date is today = harvest-ready
                'setup_date' => now()->subDays(40),
                'growth_stage' => 'harvest-ready',
            ],
        ];

        foreach ($setups as $setup) {
            HydroponicSetup::create(array_merge($setup, [
                'user_id' => $user->id,
                'device_id' => $device->id,
                'harvest_status' => $setup['harvest_status'] ?? 'not_harvested',
                'health_status' => $setup['health_status'] ?? 'good',
                'status' => $setup['status'] ?? 'active',
                'is_archived' => $setup['is_archived'] ?? false,
            ]));
        }
    }
};
