<?php

namespace Database\Seeders;

use App\Models\TipsSuggestion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipsSuggestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipsSuggestion::insert([
            [
                'title' => 'Maintain Optimal pH',
                'description' => 'Keep the water pH between 5.5 and 6.5 for most hydroponic plants.',
                'category' => 'Water Quality',
            ],
            [
                'title' => 'Check Plant Growth',
                'description' => 'Ensure your plant is growing well!',
                'category' => 'Plant Growth',
            ],
        ]);
    }
}
