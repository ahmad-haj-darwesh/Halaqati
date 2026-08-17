<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'birth_date' => $this->faker->optional()->date(),
            'guardian_name' => $this->faker->optional()->name(),
            'guardian_phone' => $this->faker->optional()->phoneNumber(),
            'national_id' => $this->faker->optional()->numerify('##########'),
            'notes' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
