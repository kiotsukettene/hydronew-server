<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => Device::factory(),
            'title' => fake()->randomElement([
                'pH Level Alert',
                'TDS Out of Range',
                'Temperature Warning',
                'System Status Update',
                'Harvest Reminder'
            ]),
            'message' => fake()->sentence(),
            'type' => fake()->randomElement(['info', 'warning', 'alert', 'success']),
            'is_read' => fake()->boolean(30), // 30% chance of being read
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }
}

