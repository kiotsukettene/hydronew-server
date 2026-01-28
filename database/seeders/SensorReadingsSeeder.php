<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\SensorSystem;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Carbon\Carbon;

class SensorReadingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systems = SensorSystem::where('device_id', Device::first()->id)->get();

        // Generate 100 readings with different days and months
        for ($i = 0; $i < 100; $i++) {
            // Generate random date within the last 12 months
            $daysAgo = rand(0, 365);
            $readingTime = Carbon::now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            foreach ($systems as $system) {
                // Add slight variations to sensor values
                $phVariation = (rand(-100, 100) / 100); // ±1.0
                $tdsVariation = (rand(-50, 50) / 100); // ±0.5
                $humidityVariation = (rand(-500, 500) / 100); // ±5.0
                $tempVariation = (rand(-200, 200) / 100); // ±2.0
                $ecVariation = (rand(-30, 30) / 100); // ±0.3
                $turbidityVariation = (rand(-50, 50) / 100); // ±0.5
                $waterLevelVariation = (rand(-30, 30) / 100); // ±0.3

                SensorReading::create([
                    'sensor_system_id' => $system->id,
                    'ph' => match ($system->system_type) {
                        'dirty_water' => round(4.08 + $phVariation, 2),
                        'clean_water' => round(7.56 + $phVariation, 2),
                        'hydroponics_water' => round(6.20 + $phVariation, 2),
                    },
                    'tds' => match ($system->system_type) {
                        'dirty_water' => round(2.49 + $tdsVariation, 2),
                        'clean_water' => round(2.43 + $tdsVariation, 2),
                        'hydroponics_water' => round(1.53 + $tdsVariation, 2),
                    },
                    'turbidity' => in_array($system->system_type, ['dirty_water', 'clean_water'])
                        ? round(2.30 + $turbidityVariation, 2)
                        : null,
                    'water_level' => in_array($system->system_type, ['dirty_water', 'clean_water'])
                        ? round(1.80 + $waterLevelVariation, 2)
                        : null,
                    'humidity' => $system->system_type === 'hydroponics_water'
                        ? round(65.00 + $humidityVariation, 2)
                        : null,
                    'temperature' => $system->system_type === 'hydroponics_water'
                        ? round(24.50 + $tempVariation, 2)
                        : null,
                    'ec' => $system->system_type === 'hydroponics_water'
                        ? round(1.90 + $ecVariation, 2)
                        : null,
                    'electric_current' => null,
                    'ai_classification' => null,
                    'confidence' => null,
                    'reading_time' => $readingTime,
                ]);
            }
        }
    }
}
