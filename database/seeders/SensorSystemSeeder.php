<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\SensorSystem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SensorSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deviceId = Device::first()->id;

        $systems = [
            [
                'device_id' => $deviceId,
                'system_type' => 'dirty_water',
                'name' => 'Dirty Water System',
                'is_active' => true,
            ],
            [
                'device_id' => $deviceId,
                'system_type' => 'clean_water',
                'name' => 'Clean Water System',
                'is_active' => true,
            ],
            [
                'device_id' => $deviceId,
                'system_type' => 'hydroponics_water',
                'name' => 'Hydroponics Water System',
                'is_active' => true,
            ],
        ];

        foreach ($systems as $system) {
            SensorSystem::firstOrCreate(
                [
                    'device_id' => $system['device_id'],
                    'system_type' => $system['system_type'],
                ],
                $system
            );
        }
    }
}
