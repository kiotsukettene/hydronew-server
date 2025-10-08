<?php

namespace Database\Seeders;

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
        HydroponicYield::create([
            'hydroponic_setup_id' => 1,
            'plant_type' => 'Lettuce',
            'growth_stage' => 'seedling',
            'harvest_status' => 'not_harvested',
            'plant_age_days' => 7,
            'health_status' => 'good',
            'estimated_harvest_date' => now()->addDays(25),
        ]);
    }
}
