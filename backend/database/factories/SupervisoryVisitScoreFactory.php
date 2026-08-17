<?php

namespace Database\Factories;

use App\Models\SupervisoryVisitScore;
use App\Models\SupervisoryVisit;
use App\Models\SupervisionRubricItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisoryVisitScore>
 */
class SupervisoryVisitScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supervisory_visit_id' => SupervisoryVisit::factory(),
            'supervision_rubric_item_id' => SupervisionRubricItem::factory(),
            'score' => $this->faker->optional()->randomFloat(2, 0, 5),
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}
