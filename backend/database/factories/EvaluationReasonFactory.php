<?php

namespace Database\Factories;

use App\Models\EvaluationReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationReason>
 */
class EvaluationReasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'label' => $this->faker->word(),
            'type' => $this->faker->randomElement([
                EvaluationReason::TYPE_EXCELLENCE,
                EvaluationReason::TYPE_DEFICIENCY,
            ]),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
