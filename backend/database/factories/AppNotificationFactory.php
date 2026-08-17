<?php

namespace Database\Factories;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppNotificationFactory extends Factory
{
    protected $model = AppNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'type' => fake()->randomElement(['general', 'record_reminder', 'supervisory_visit', 'exam_result', 'unrecorded_halaqah']),
            'data' => [
                'type' => fake()->randomElement(['general', 'record_reminder', 'supervisory_visit', 'exam_result']),
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'read_at' => fake()->boolean(30) ? now() : null,
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
