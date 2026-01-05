<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Device;
use App\Models\DeviceUser;

class DeviceUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $device = Device::first();
        DeviceUser::firstOrCreate([
            'user_id' => '1',
            'device_id' => $device->id,
            'token' => 'sample_paring_token_123456',
            'expires_at' => now()->addHours(2),
        ]);

    }
}
