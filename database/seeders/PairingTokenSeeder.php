<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PairingToken;

class PairingTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PairingToken::firstOrCreate([
            'user_id' => 1,
            'token_hash' => hash('sha256', 'sample_token'),
            'expires_at' => now()->addHours(1),
            'used_at' => null,
        ]);
    }
}
