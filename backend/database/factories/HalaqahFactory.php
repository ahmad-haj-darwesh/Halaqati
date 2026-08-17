<?php

namespace Database\Factories;

use App\Models\Halaqah;
use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Halaqah>
 */
class HalaqahFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Halaqah ' . $this->faker->unique()->word(),
            'center_id' => Center::factory(),
            'description' => $this->faker->optional()->sentence(),
            'capacity' => 20,
        ];
    }
}
