<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Test>
 */
class TestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => Test::TYPE_REGULAR,
            'title' => 'اختبار ' . $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'scope_halaqah_id' => Halaqah::factory(),
            'scope_center_id' => null,
            'scope_region_id' => null,
            'scheduled_at' => now(),
            'created_by_user_id' => User::factory(),
            'is_published' => false,
            'sampling_strategy' => null,
            'sampling_count' => null,
            'sampling_percent' => null,
            'sampling_seed' => null,
            'sampling_active_only' => true,
        ];
    }
}
