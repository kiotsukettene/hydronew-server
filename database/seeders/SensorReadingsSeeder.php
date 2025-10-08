<?php

namespace Database\Seeders;

use App\Models\Sensor;
use App\Models\SensorReading;
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
        $sensors = Sensor::all();

        foreach ($sensors as $sensor) {
            for ($i = 0; $i < 10; $i++) {
                SensorReading::create([
                    'sensor_id' => $sensor->id,
                    'reading_value' => match ($sensor->type) {
                        'ph' => number_format(rand(55, 75) / 10, 1),
                        'turbidity' => rand(1, 10),
                        'TDS' => rand(150, 350),
                        'temperature' => number_format(rand(200, 300) / 10, 1),
                        'water_level' => rand(60, 100),
                        'electric_current' => number_format(rand(100, 200) / 10, 1),
                        default => rand(1, 100)
                    },
                    'reading_time' => Carbon::now()->subMinutes(rand(1, 100)),
                ]);
            }
        }
    }
}
