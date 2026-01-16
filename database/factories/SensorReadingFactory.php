<?php

namespace Database\Factories;

use App\Models\SensorReading;
use App\Models\SensorSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SensorReading>
 */
class SensorReadingFactory extends Factory
{
    protected $model = SensorReading::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sensor_system_id' => SensorSystem::factory(),
            'ph' => fake()->optional()->randomFloat(2, 5.5, 8.5),
            'tds' => fake()->optional()->randomFloat(2, 200, 2000),
            'turbidity' => fake()->optional()->randomFloat(2, 0, 100),
            'water_level' => fake()->optional()->randomFloat(2, 0, 100),
            'humidity' => fake()->optional()->randomFloat(2, 30, 90),
            'temperature' => fake()->optional()->randomFloat(2, 15, 35),
            'ec' => fake()->optional()->randomFloat(2, 0.5, 3.0),
            'electric_current' => fake()->optional()->randomFloat(2, 0, 10),
            'reading_time' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}

