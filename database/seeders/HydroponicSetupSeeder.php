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
            'bed_size' => 'medium',
            'water_amount' => '20L',
            'setup_date' => now(),
            'status' => 'active',
        ]);
    }
}
