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
use Database\Seeders\HydroponicYieldGradeSeeder;
use Database\Seeders\TipsSuggestionsSeeder;
use Database\Seeders\TreatmentStagesSeeder;
use Database\Seeders\TreatmentReportsSeeder;
use Database\Seeders\FiltrationProcessSeeder;
use Database\Seeders\DeviceUserSeeder;
use Database\Seeders\PairingTokenSeeder;
use Database\Seeders\AdminSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            AdminSeeder::class,
            UsersSeeder::class,
            DevicesSeeder::class,
            SensorSystemSeeder::class,
            SensorReadingsSeeder::class,
            TreatmentReportsSeeder::class,
            TreatmentStagesSeeder::class,
            FiltrationProcessSeeder::class, // Seed filtration processes after treatment reports
            HydroponicSetupSeeder::class,
            HydroponicYieldSeeder::class,
            HydroponicYieldGradeSeeder::class,
            TipsSuggestionsSeeder::class,
            NotificationSeeder::class,
            HelpCenterSeeder::class,
            DeviceUserSeeder::class,
            PairingTokenSeeder::class,
        ]);
    }
}
