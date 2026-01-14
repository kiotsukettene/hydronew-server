<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\SensorSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SensorSystem>
 */
class SensorSystemFactory extends Factory
{
    protected $model = SensorSystem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'system_type' => fake()->randomElement(['clean_water', 'dirty_water', 'hydroponics_water']),
            'name' => fake()->randomElement(['pH Sensor', 'TDS Sensor', 'Temperature Sensor', 'Humidity Sensor']),
            'is_active' => fake()->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the sensor system is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}

