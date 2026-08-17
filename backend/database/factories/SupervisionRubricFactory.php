<?php

namespace Database\Factories;

use App\Models\SupervisionRubric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisionRubric>
 */
class SupervisionRubricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'نموذج تقييم الزيارة - ' . $this->faker->word(),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'created_by_user_id' => User::factory(),
        ];
    }
}
