<?php

namespace Database\Seeders;

use App\Models\EvaluationReason;
use Illuminate\Database\Seeder;

class EvaluationReasonsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Excellence
            ['key' => 'ex_homework', 'label' => 'واجب', 'type' => EvaluationReason::TYPE_EXCELLENCE, 'sort_order' => 10],
            ['key' => 'ex_adab', 'label' => 'أدب', 'type' => EvaluationReason::TYPE_EXCELLENCE, 'sort_order' => 20],
            ['key' => 'ex_tarbiyah', 'label' => 'تربية', 'type' => EvaluationReason::TYPE_EXCELLENCE, 'sort_order' => 30],

            // Deficiency
            ['key' => 'df_homework', 'label' => 'واجب', 'type' => EvaluationReason::TYPE_DEFICIENCY, 'sort_order' => 10],
            ['key' => 'df_memorization', 'label' => 'حفظ', 'type' => EvaluationReason::TYPE_DEFICIENCY, 'sort_order' => 20],
            ['key' => 'df_revision', 'label' => 'مراجعة', 'type' => EvaluationReason::TYPE_DEFICIENCY, 'sort_order' => 30],
            ['key' => 'df_tajweed', 'label' => 'تجويد', 'type' => EvaluationReason::TYPE_DEFICIENCY, 'sort_order' => 40],
            ['key' => 'df_discipline', 'label' => 'انضباط', 'type' => EvaluationReason::TYPE_DEFICIENCY, 'sort_order' => 50],
        ];

        foreach ($items as $item) {
            EvaluationReason::updateOrCreate(
                ['key' => $item['key']],
                [
                    'label' => $item['label'],
                    'type' => $item['type'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }
    }
}

