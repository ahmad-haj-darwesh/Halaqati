<?php

namespace App\Filament\Widgets;

use App\Models\SupervisorFieldVisit;
use Filament\Widgets\ChartWidget;

/**
 * ويدجت مقارنة نتائج الزيارات الميدانية (آخر 6 أشهر) في لوحة Filament.
 *
 * Arabic: يحسب متوسطات شهرية لعدة محاور (مهارة الإعطاء/الالتزام بالخطة/تفاعل الطلاب) من جدول
 * `SupervisorFieldVisit` ثم يعرضها كمخطط خطّي للمقارنة.
 * EN: Line chart widget comparing monthly averages for field-visit scores over the last 6 months.
 */
class VisitScoresComparisonWidget extends ChartWidget
{
    protected static ?string $heading = 'مقارنة نتائج الزيارات الميدانية — آخر 6 أشهر';

    protected static ?int $sort = 43;

    protected int|string|array $columnSpan = 'full';

    /**
     * بيانات المخطط (Labels/Datasets).
     * EN: Chart data.
     */
    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));
        $labels = $months->map(fn ($m) => $m->translatedFormat('M Y'))->toArray();

        $teaching = $months->map(fn ($m) => (float) round(
            (float) (SupervisorFieldVisit::query()
                ->whereMonth('visit_date', $m->month)
                ->whereYear('visit_date', $m->year)
                ->avg('teaching_skill_score') ?? 0),
            1
        ))->toArray();

        $plan = $months->map(fn ($m) => (float) round(
            (float) (SupervisorFieldVisit::query()
                ->whereMonth('visit_date', $m->month)
                ->whereYear('visit_date', $m->year)
                ->avg('plan_adherence_score') ?? 0),
            1
        ))->toArray();

        $engagement = $months->map(fn ($m) => (float) round(
            (float) (SupervisorFieldVisit::query()
                ->whereMonth('visit_date', $m->month)
                ->whereYear('visit_date', $m->year)
                ->avg('student_engagement_score') ?? 0),
            1
        ))->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'مهارة الإعطاء',
                    'data' => $teaching,
                    'borderColor' => '#0F6B4F',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'الالتزام بالخطة',
                    'data' => $plan,
                    'borderColor' => '#C08A2E',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'تفاعل الطلاب',
                    'data' => $engagement,
                    'borderColor' => '#7FB79F',
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * نوع المخطط.
     * EN: Chart type.
     */
    protected function getType(): string
    {
        return 'line';
    }
}
