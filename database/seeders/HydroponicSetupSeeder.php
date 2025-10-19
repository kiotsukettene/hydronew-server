<?php

namespace Database\Seeders;

use App\Models\HydroponicSetup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HydroponicSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HydroponicSetup::create([
            'user_id' => 1,
            'crop_name' => 'Lettuce',
            'number_of_crops' => 30,
            'bed_size' => 'medium',
            'pump_config' => json_encode([
                'pump_speed' => 'medium',
                'schedule' => '6h_on_2h_off',
                'backup_pump' => true,
            ]),
            'nutrient_solution' => 'HydroGro Mix A+B',
            'target_ph_min' => 5.5,
            'target_ph_max' => 6.5,
            'target_tds_min' => 800,
            'target_tds_max' => 1000,
            'water_amount' => '20L',
            'setup_date' => now(),
            'status' => 'active',
        ]);
    }
};
