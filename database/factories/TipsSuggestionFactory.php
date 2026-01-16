<?php

namespace Database\Factories;

use App\Models\TipsSuggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TipsSuggestion>
 */
class TipsSuggestionFactory extends Factory
{
    protected $model = TipsSuggestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6, true),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['water_quality', 'nutrients', 'pest_control', 'harvesting', 'general']),
        ];
    }
}

