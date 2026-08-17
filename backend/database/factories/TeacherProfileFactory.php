<?php

namespace Database\Factories;

use App\Models\Halaqah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherProfileFactory extends Factory
{
    protected $model = TeacherProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'halaqah_id' => Halaqah::factory(),
            'phone' => fake()->phoneNumber(),
            'qualification' => fake()->randomElement(['ثانوي', 'جامعي', 'ماجستير', 'دكتوراه']),
            'hire_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
            'photo_path' => fake()->optional()->filePath(),
        ];
    }
}
