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
            'harvest_status' => 'not_harvested',
            'growth_stage' => 'seedling',
            'health_status' => 'good',
            'predicted_yield' => 45.75,
            'actual_yield' => 10.20,
            'harvest_date' => now()->subDays(3),
            'system_generated' => false,
            'notes' => 'Lettuce seedlings are healthy and under optimal pH range.',
        ]);
    }
}
