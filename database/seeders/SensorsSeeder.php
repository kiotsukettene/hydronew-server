<?php

namespace Database\Seeders;

use App\Models\Device;
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
        $device = Device::first();
        $types = [
            ['ph', 'pH'],
            ['tds', 'ppm'],
            ['turbidity', 'NTU'] ,
            ['temperature', '°C'],
            ['water_level', 'L'],
            ['humidity', '%'],
            ['ec', 'µS/cm'],
            ['electric_current', 'V'],
        ];

        foreach ($types as [$type, $unit]) {
            Sensor::create([
                'device_id' => $device->id,
                'type' => $type,
                'unit' => $unit,
            ]);
        }
    }
}
