<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            'password' => Hash::make('Momorevillame@24'),
        ]);
    }
}
