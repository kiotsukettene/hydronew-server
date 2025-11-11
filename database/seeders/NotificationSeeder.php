<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $device = Device::first();
        Notification::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'title' => 'Water Treatment',
            'message' => 'Your water treatment cycle has completed successfully.',
            'type' => 'info',
            'is_read' => false,
            'created_at' => now()
        ]);
    }
}
