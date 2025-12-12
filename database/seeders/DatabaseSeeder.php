<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\DevicesSeeder;
use Database\Seeders\SensorsSeeder;
use Database\Seeders\NotificationSeeder;
use Database\Seeders\SensorReadingsSeeder;
use Database\Seeders\HydroponicSetupSeeder;
use Database\Seeders\HydroponicYieldSeeder;
use Database\Seeders\TipsSuggestionsSeeder;
use Database\Seeders\TreatmentStagesSeeder;
use Database\Seeders\TreatmentReportsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            UsersSeeder::class,
            DevicesSeeder::class,
            SensorsSeeder::class,
            SensorReadingsSeeder::class,
            TreatmentReportsSeeder::class,
            TreatmentStagesSeeder::class,
            HydroponicSetupSeeder::class,
            HydroponicYieldSeeder::class,
            HydroponicSetupLogSeeder::class,
            TipsSuggestionsSeeder::class,
            NotificationSeeder::class,
            HelpCenterSeeder::class,
        ]);
    }
}
