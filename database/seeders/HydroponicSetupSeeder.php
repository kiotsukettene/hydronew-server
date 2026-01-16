<?php

namespace Database\Seeders;

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

        if (!$user) {
            return; // Safety check
        }

        $setups = [
            [
                'crop_name' => 'Olmetie',
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
                'setup_date' => now()->subDays(10),
                'growth_stage' => 'seedling',
            ],
            [
                'crop_name' => 'Lettuce',
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
                'setup_date' => now()->subDays(15),
                'growth_stage' => 'vegetative',
            ],
            [
                'crop_name' => 'Basil',
                'number_of_crops' => 40,
                'bed_size' => 'medium',
                'pump_config' => ['pump1' => 'on'],
                'nutrient_solution' => 'Herb Mix A',
                'target_ph_min' => 5.5,
                'target_ph_max' => 6.5,
                'target_tds_min' => 700,
                'target_tds_max' => 1200,
                'water_amount' => '18L',
                'harvest_date' => now()->addDays(10),
                'setup_date' => now()->subDays(25),
                'growth_stage' => 'vegetative',
            ],
            [
                'crop_name' => 'Spinach',
                'number_of_crops' => 60,
                'bed_size' => 'large',
                'pump_config' => ['pump1' => 'on', 'pump2' => 'off'],
                'nutrient_solution' => 'Leafy Green Mix',
                'target_ph_min' => 6.0,
                'target_ph_max' => 7.0,
                'target_tds_min' => 600,
                'target_tds_max' => 1000,
                'water_amount' => '28L',
                'harvest_date' => now()->addDays(25),
                'setup_date' => now()->subDays(18),
                'growth_stage' => 'vegetative',
                'health_status' => 'moderate',
            ],
            [
                'crop_name' => 'Kale',
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
                'setup_date' => now()->subDays(5),
                'growth_stage' => 'seedling',
            ],
            [
                'crop_name' => 'Mint',
                'number_of_crops' => 25,
                'bed_size' => 'small',
                'pump_config' => ['pump1' => 'off'],
                'nutrient_solution' => 'Herb Nutrient B',
                'target_ph_min' => 6.0,
                'target_ph_max' => 7.0,
                'target_tds_min' => 500,
                'target_tds_max' => 900,
                'water_amount' => '10L',
                'harvest_date' => now()->subDays(2),
                'setup_date' => now()->subDays(45),
                'growth_stage' => 'harvest-ready',
                'harvest_status' => 'harvested',
                'status' => 'inactive',
                'is_archived' => true,
            ],
            [
                'crop_name' => 'Cherry Tomato',
                'number_of_crops' => 20,
                'bed_size' => 'large',
                'pump_config' => ['pump1' => 'on', 'pump2' => 'on'],
                'nutrient_solution' => 'Tomato Bloom Mix',
                'target_ph_min' => 5.8,
                'target_ph_max' => 6.3,
                'target_tds_min' => 1200,
                'target_tds_max' => 2000,
                'water_amount' => '40L',
                'harvest_date' => now()->addDays(40),
                'setup_date' => now()->subDays(30),
                'growth_stage' => 'flowering',
            ],
            [
                'crop_name' => 'Cucumber',
                'number_of_crops' => 18,
                'bed_size' => 'large',
                'pump_config' => ['pump1' => 'on'],
                'nutrient_solution' => 'Fruit Crop Mix',
                'target_ph_min' => 5.5,
                'target_ph_max' => 6.0,
                'target_tds_min' => 1000,
                'target_tds_max' => 1800,
                'water_amount' => '35L',
                'harvest_date' => now()->addDays(28),
                'setup_date' => now()->subDays(20),
                'growth_stage' => 'vegetative',
                'health_status' => 'moderate',
            ],
            [
                'crop_name' => 'Pak Choi',
                'number_of_crops' => 70,
                'bed_size' => 'medium',
                'pump_config' => ['pump1' => 'on'],
                'nutrient_solution' => 'Leafy Nutrient Pro',
                'target_ph_min' => 6.0,
                'target_ph_max' => 7.0,
                'target_tds_min' => 600,
                'target_tds_max' => 1100,
                'water_amount' => '22L',
                'harvest_date' => now()->addDays(14),
                'setup_date' => now()->subDays(23),
                'growth_stage' => 'vegetative',
            ],
            [
                'crop_name' => 'Arugula',
                'number_of_crops' => 55,
                'bed_size' => 'medium',
                'pump_config' => ['pump1' => 'on'],
                'nutrient_solution' => 'Rapid Grow Mix',
                'target_ph_min' => 6.0,
                'target_ph_max' => 6.8,
                'target_tds_min' => 500,
                'target_tds_max' => 900,
                'water_amount' => '20L',
                'harvest_date' => now()->addDays(5),
                'setup_date' => now()->subDays(35),
                'growth_stage' => 'harvest-ready',
            ],
        ];

        foreach ($setups as $setup) {
            HydroponicSetup::create(array_merge($setup, [
                'user_id' => $user->id,
                'harvest_status' => $setup['harvest_status'] ?? 'not_harvested',
                'health_status' => $setup['health_status'] ?? 'good',
                'status' => $setup['status'] ?? 'active',
                'is_archived' => $setup['is_archived'] ?? false,
            ]));
        }
    }
};
