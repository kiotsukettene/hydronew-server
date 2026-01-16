<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Device;
use App\Models\DeviceUser;
use App\Models\User;

class DeviceUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $device = Device::first();
        DeviceUser::firstOrCreate([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

    }
}
