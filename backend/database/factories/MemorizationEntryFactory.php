<?php

namespace Database\Factories;

use App\Models\MemorizationEntry;
use App\Models\Halaqah;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemorizationEntry>
 */
class MemorizationEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'halaqah_id' => Halaqah::factory(),
            'student_id' => Student::factory(),
            'date' => now()->toDateString(),
            'memorization_from' => $this->faker->optional()->randomElement(['البقرة 1', 'آل عمران 10']),
            'memorization_to' => $this->faker->optional()->randomElement(['البقرة 5', 'آل عمران 20']),
            'revision_from' => $this->faker->optional()->randomElement(['الفاتحة', 'الناس']),
            'revision_to' => $this->faker->optional()->randomElement(['الكافرون', 'الفلق']),
            'mistakes' => $this->faker->optional()->sentence(),
        ];
    }
}
