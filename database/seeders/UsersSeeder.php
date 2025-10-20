<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'kiotsuketteneloreto@gmail.com'],
            [
                'first_name' => 'Momo',
                'last_name' => 'Revillame',
                'email_verified_at' => now(),
                'password' => bcrypt('password123'), // change as needed
            ]
        );
    }
}
