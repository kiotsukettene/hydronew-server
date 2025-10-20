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
        User::create([
            'first_name' => 'momo',
            'last_name' => 'revillame',
            'email' => 'kiotsuketteneloreto@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Momorevillame@24'),
        ]);
    }
}
