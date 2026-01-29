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

        foreach ($systems as $system) {
            SensorReading::create([
                'sensor_system_id' => $system->id,
                'ph' => match ($system->system_type) {
                    'dirty_water' => 4.08,
                    'clean_water' => 7.56,
                    'hydroponics_water' => 6.20,
                },
                'tds' => match ($system->system_type) {
                    'dirty_water' => 2.49,
                    'clean_water' => 2.43,
                    'hydroponics_water' => 1.53,
                },
                'turbidity' => in_array($system->system_type, ['dirty_water', 'clean_water'])
                    ? 2.30
                    : null,
                'water_level' => in_array($system->system_type, ['dirty_water', 'clean_water'])
                    ? 1.80
                    : null,
                'humidity' => $system->system_type === 'hydroponics_water'
                    ? 65.00
                    : null,
                'temperature' => $system->system_type === 'hydroponics_water'
                    ? 24.50
                    : null,
                'ec' => $system->system_type === 'hydroponics_water'
                    ? 1.90
                    : null,
                'electric_current' => null,
                'ai_classification' => null,
                'confidence' => null,
                'reading_time' => Carbon::now(),
            ]);
        }
    }
}
