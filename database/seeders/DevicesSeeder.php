<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Device::create([
            'user_id' => 1,
            'name' => 'HydroNew Device A-1',
            'serial_number' => 'MFC-1204328HD0B45',
            'status' => 'connected',
        ]);
    }
}
