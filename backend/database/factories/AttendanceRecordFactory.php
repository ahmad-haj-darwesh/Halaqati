<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Halaqah;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
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
            'status' => $this->faker->randomElement([
                AttendanceRecord::STATUS_PRESENT,
                AttendanceRecord::STATUS_EXCUSED,
                AttendanceRecord::STATUS_UNEXCUSED,
            ]),
            'recorded_by_user_id' => User::factory(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
