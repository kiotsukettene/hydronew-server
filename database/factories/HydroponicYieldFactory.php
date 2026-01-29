<?php

namespace Database\Factories;

use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HydroponicYield>
 */
class HydroponicYieldFactory extends Factory
{
    protected $model = HydroponicYield::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hydroponic_setup_id' => HydroponicSetup::factory(),
            'total_weight' => fake()->randomFloat(2, 1, 50),
            'total_count' => fake()->numberBetween(10, 200),
            'notes' => fake()->optional()->sentence(),
            'is_archived' => fake()->boolean(10),
        ];
    }
}

