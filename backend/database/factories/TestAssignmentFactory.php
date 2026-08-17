<?php

namespace Database\Factories;

use App\Models\Halaqah;
use App\Models\Student;
use App\Models\TestAssignment;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestAssignment>
 */
class TestAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'student_id' => Student::factory(),
            'halaqah_id' => Halaqah::factory(),
            'assigned_at' => now(),
            'assigned_by_user_id' => User::factory(),
            'status' => TestAssignment::STATUS_ASSIGNED,
        ];
    }
}
