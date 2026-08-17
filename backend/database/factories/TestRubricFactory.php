<?php

namespace Database\Factories;

use App\Models\TestRubric;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestRubric>
 */
class TestRubricFactory extends Factory
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
            'name' => $this->faker->randomElement(['حفظ', 'تجويد', 'مراجعة', 'سلوك/أدب']),
            'max_score' => 25,
            'weight' => null,
            'sort_order' => 0,
        ];
    }
}
