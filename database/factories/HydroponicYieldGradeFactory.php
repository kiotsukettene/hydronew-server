<?php

namespace Database\Factories;

use App\Models\HydroponicYield;
use App\Models\HydroponicYieldGrade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HydroponicYieldGrade>
 */
class HydroponicYieldGradeFactory extends Factory
{
    protected $model = HydroponicYieldGrade::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hydroponic_yield_id' => HydroponicYield::factory(),
            'grade' => fake()->randomElement(['A', 'B', 'C']),
            'count' => fake()->numberBetween(5, 50),
            'weight' => fake()->randomFloat(2, 0.5, 10),
        ];
    }
}

