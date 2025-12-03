<?php

namespace Database\Seeders;

use App\Models\HydroponicSetup;
use App\Models\HydroponicSetupLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HydroponicSetupLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $setups = HydroponicSetup::all();

        foreach ($setups as $setup) {
            // Example: 5 logs per setup
            for ($i = 1; $i <= 5; $i++) {
                HydroponicSetupLog::create([
                    'hydroponic_setup_id' => $setup->id,
                    'growth_stage' => ['seedling', 'vegetative', 'flowering', 'harvest-ready'][rand(0, 3)],
                    'ph_status' => rand(55, 65) / 10,
                    'tds_status' => rand(400, 800),
                    'ec_status' => rand(1, 3),
                    'humidity_status' => rand(60, 80),
                    'health_status' => ['good', 'moderate', 'poor'][rand(0, 2)],
                    'harvest_date' => null,
                    'system_generated' => true,
                    'notes' => 'Routine log entry.'
                ]);
            }
        }
    }
}
