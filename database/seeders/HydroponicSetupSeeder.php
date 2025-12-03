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
        HydroponicSetup::create([
            'user_id' => $user->id,
            'crop_name' => 'Olmetie',
            'number_of_crops' => 50,
            'bed_size' => 'medium',
            'pump_config' => json_encode(['pump1' => 'on', 'pump2' => 'off']),
            'nutrient_solution' => 'NPK 20-20-20',
            'target_ph_min' => 5.5,
            'target_ph_max' => 6.5,
            'target_tds_min' => 400,
            'target_tds_max' => 800,
            'water_amount' => '20L',
            'setup_date' => now()->subDays(10),
            'status' => 'active',
            'is_archived' => false
        ]);
    }
};
