<?php

namespace Database\Seeders;

use App\Models\Sensor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SensorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['ph', 'pH'],
            ['turbidity', 'NTU'],
            ['TDS', 'ppm'],
            ['temperature', '°C'],
            ['water_level', 'L'],
            ['humidity', '%'],
            ['EC', 'µS/cm'],
            ['electric_current', 'V'],
        ];

        foreach ($types as [$type, $unit]) {
            Sensor::create([
                'device_id' => 1,
                'type' => $type,
                'unit' => $unit,
            ]);
        }
    }
}
