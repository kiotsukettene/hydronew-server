<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\TreatmentReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TreatmentReport>
 */
class TreatmentReportFactory extends Factory
{
    protected $model = TreatmentReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('-1 month', '-1 day');
        $endTime = fake()->optional(0.7)->dateTimeBetween($startTime, 'now');

        return [
            'device_id' => Device::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'final_status' => $endTime ? fake()->randomElement(['completed', 'failed', 'interrupted']) : null,
            'total_cycles' => $endTime ? fake()->numberBetween(1, 100) : null,
        ];
    }

    /**
     * Indicate that the treatment is ongoing.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_time' => null,
            'final_status' => null,
            'total_cycles' => null,
        ]);
    }

    /**
     * Indicate that the treatment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_time' => fake()->dateTimeBetween($attributes['start_time'], 'now'),
            'final_status' => 'completed',
            'total_cycles' => fake()->numberBetween(50, 100),
        ]);
    }
}

