<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Device::firstOrCreate([
            'device_name' => 'Biotech01',
            'serial_number' => 'BT-2025-0001',
            'model' => 'Raspbery Pi 5',
            'firmware_version' => '094/11/7',
            'status' => 'online',
        ]);
    }
}
