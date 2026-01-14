<?php

namespace Database\Factories;

use App\Models\HydroponicSetup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HydroponicSetup>
 */
class HydroponicSetupFactory extends Factory
{
    protected $model = HydroponicSetup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'crop_name' => fake()->randomElement(['Lettuce', 'Tomato', 'Cucumber', 'Spinach', 'Basil', 'Strawberry']),
            'number_of_crops' => fake()->numberBetween(10, 100),
            'bed_size' => fake()->randomElement(['Small (1x2m)', 'Medium (2x4m)', 'Large (4x8m)']),
            'pump_config' => [
                'pump_type' => fake()->randomElement(['submersible', 'inline']),
                'flow_rate' => fake()->numberBetween(100, 500),
            ],
            'nutrient_solution' => fake()->randomElement(['A+B Formula', 'General Purpose', 'Bloom Boost']),
            'target_ph_min' => fake()->randomFloat(1, 5.5, 6.0),
            'target_ph_max' => fake()->randomFloat(1, 6.5, 7.0),
            'target_tds_min' => fake()->randomFloat(0, 800, 1000),
            'target_tds_max' => fake()->randomFloat(0, 1200, 1500),
            'water_amount' => fake()->numberBetween(50, 500) . ' liters',
            'setup_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'harvest_date' => fake()->dateTimeBetween('now', '+3 months'),
            'harvest_status' => fake()->randomElement(['pending', 'ready', 'harvested']),
            'status' => fake()->randomElement(['active', 'inactive', 'maintenance']),
            'growth_stage' => fake()->randomElement(['seedling', 'vegetative', 'flowering', 'harvest']),
            'health_status' => fake()->randomElement(['healthy', 'needs attention', 'critical']),
            'is_archived' => fake()->boolean(10), // 10% chance of being archived
        ];
    }

    /**
     * Indicate that the hydroponic setup is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the hydroponic setup is harvested.
     */
    public function harvested(): static
    {
        return $this->state(fn (array $attributes) => [
            'harvest_status' => 'harvested',
            'harvest_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}

