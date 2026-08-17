<?php

namespace Database\Factories;

use App\Models\DailyEvaluation;
use App\Models\Halaqah;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyEvaluation>
 */
class DailyEvaluationFactory extends Factory
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
            'overall' => $this->faker->randomElement([
                DailyEvaluation::OVERALL_EXCELLENT,
                DailyEvaluation::OVERALL_GOOD,
                DailyEvaluation::OVERALL_NEEDS_IMPROVEMENT,
                DailyEvaluation::OVERALL_NONE,
            ]),
            'recorded_by_user_id' => User::factory(),
            'general_note' => $this->faker->optional()->sentence(),
        ];
    }
}
