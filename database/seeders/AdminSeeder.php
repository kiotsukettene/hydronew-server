<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'Momo',
            'email' => 'adminmomo@hydronew.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'profile_picture' => null,
            'address' => null,
            'first_time_login' => false,
            'last_login_at' => null,
            'verification_code' => null,
            'verification_expires_at' => null,
            'last_otp_sent_at' => null,
            'role' => 'admin',
            'status' => 'active',
            'is_archived' => false,
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
