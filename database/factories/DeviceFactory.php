<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_name' => fake()->words(3, true),
            'serial_number' => 'SN-' . strtoupper(fake()->bothify('??-####-????')),
            'model' => fake()->words(2, true),
            'firmware_version' => fake()->numerify('#.#.#'),
            'status' => fake()->randomElement(['online', 'offline']),
        ];
    }
}

