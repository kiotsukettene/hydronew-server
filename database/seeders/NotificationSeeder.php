<?php

namespace Database\Seeders;

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
        Notification::create([
            'user_id' => $user->id,
            'device_id' => 1,
            'message' => 'Your water treatment cycle has completed successfully.',
            'is_read' => false,
        ]);
    }
}
