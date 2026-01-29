<?php

namespace Database\Factories;

use App\Models\HelpCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HelpCenter>
 */
class HelpCenterFactory extends Factory
{
    protected $model = HelpCenter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => fake()->sentence() . '?',
            'answer' => fake()->paragraph(),
        ];
    }
}

