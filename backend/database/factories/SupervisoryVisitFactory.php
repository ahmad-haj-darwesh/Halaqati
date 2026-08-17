<?php

namespace Database\Factories;

use App\Models\SupervisoryVisit;
use App\Models\Center;
use App\Models\Halaqah;
use App\Models\SupervisionRubric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisoryVisit>
 */
class SupervisoryVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supervision_rubric_id' => SupervisionRubric::factory(),
            'supervisor_user_id' => User::factory(),
            'center_id' => Center::factory(),
            'halaqah_id' => Halaqah::factory(),
            'teacher_user_id' => User::factory(),
            'visited_at' => now(),
            'duration_minutes' => $this->faker->optional()->numberBetween(15, 120),
            'overall_level' => $this->faker->optional()->randomElement(['excellent', 'good', 'acceptable', 'weak']),
            'overall_score' => null,
            'summary' => $this->faker->optional()->sentence(),
            'recommendations' => $this->faker->optional()->sentence(),
            'is_finalized' => false,
        ];
    }
}
