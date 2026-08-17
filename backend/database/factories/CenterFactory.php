<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Center>
 */
class CenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Center ' . $this->faker->unique()->company(),
            'region_id' => Region::factory(),
            'admin_user_id' => null,
            'address' => $this->faker->optional()->address(),
            'phone' => $this->faker->optional()->phoneNumber(),
        ];
    }
}
