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
            'device_name' => 'HydroNew Device A-1',
            'serial_number' => 'MFC-1204328HD0B45',
            'model' => 'HydroNew Model A',
            'firmware_version' => '1.0.0',
            'status' => 'offline',
        ]);
    }
}
