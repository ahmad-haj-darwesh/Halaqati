<?php

namespace Database\Factories;

use App\Models\SupervisionRubricItem;
use App\Models\SupervisionRubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisionRubricItem>
 */
class SupervisionRubricItemFactory extends Factory
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
            'key' => $this->faker->unique()->slug(2),
            'label' => $this->faker->sentence(3),
            'max_score' => 5,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
