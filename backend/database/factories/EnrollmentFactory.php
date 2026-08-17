<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'halaqah_id' => Halaqah::factory(),
            'enrolled_at' => now()->toDateString(),
            'status' => Enrollment::STATUS_ACTIVE,
            'left_at' => null,
            'leave_reason' => null,
        ];
    }
}
