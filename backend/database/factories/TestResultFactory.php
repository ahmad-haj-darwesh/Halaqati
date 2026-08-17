<?php

namespace Database\Factories;

use App\Models\TestResult;
use App\Models\TestAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestResult>
 */
class TestResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_assignment_id' => TestAssignment::factory(),
            'examiner_user_id' => User::factory(),
            'total_score' => $this->faker->optional()->randomFloat(2, 0, 100),
            'level' => $this->faker->optional()->randomElement([
                TestResult::LEVEL_EXCELLENT,
                TestResult::LEVEL_GOOD,
                TestResult::LEVEL_ACCEPTABLE,
                TestResult::LEVEL_WEAK,
            ]),
            'notes' => $this->faker->optional()->sentence(),
            'tested_at' => now(),
        ];
    }
}
