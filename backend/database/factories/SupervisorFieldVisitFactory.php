<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\User;
use App\Models\SupervisorFieldVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupervisorFieldVisitFactory extends Factory
{
    protected $model = SupervisorFieldVisit::class;

    public function definition(): array
    {
        return [
            'supervisor_user_id' => User::factory(),
            'teacher_user_id' => User::factory(),
            'center_id' => Center::factory(),
            'visit_date' => fake()->date(),
            'teaching_skill_score' => fake()->numberBetween(1, 10),
            'plan_adherence_score' => fake()->numberBetween(1, 10),
            'student_engagement_score' => fake()->numberBetween(1, 10),
            'notes' => fake()->optional()->sentence(),
            'recommendations' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
        ];
    }
}
