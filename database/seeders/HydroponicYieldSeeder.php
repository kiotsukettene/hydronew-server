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
        $hydroponicSetup = HydroponicSetup::first();
        HydroponicYield::create([
            'hydroponic_setup_id' => $hydroponicSetup->id,
                'total_weight' => rand(2000, 5000), // grams
                'total_count' => rand(40, 50),
                'quality_grade' => 'selling',
                'notes' => 'Healthy harvest.',
                'is_archived' => false
        ]);
    }
}
