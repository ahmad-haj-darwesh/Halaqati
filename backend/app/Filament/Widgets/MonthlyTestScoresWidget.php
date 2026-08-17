<?php

namespace App\Filament\Widgets;

use App\Models\TestResult;
use Filament\Widgets\ChartWidget;

/**
 * ويدجت متوسط نتائج الاختبارات (آخر 6 أشهر) في لوحة Filament.
 *
 * Arabic: يحسب متوسط `total_score` لكل شهر خلال آخر 6 أشهر ويعرضه كمخطط خطّي.
 * ملاحظة: يعتمد على `created_at` في `TestResult` (وقت إدخال النتيجة) وليس `tested_at`.
 * EN: Line chart showing monthly average test scores over the last 6 months.
 */
class MonthlyTestScoresWidget extends ChartWidget
{
    protected static ?string $heading = 'متوسط نتائج الاختبارات — آخر 6 أشهر';

    protected static ?int $sort = 41;

    protected int|string|array $columnSpan = 'half';

    /**
     * بيانات المخطط (Labels/Datasets).
     * EN: Chart data.
     */
    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $labels = $months->map(fn ($m) => $m->translatedFormat('M Y'))->toArray();

        $data = $months->map(fn ($m) => (float) round(
            (float) (TestResult::query()
                ->whereMonth('created_at', $m->month)
                ->whereYear('created_at', $m->year)
                ->avg('total_score') ?? 0),
            1
        ))->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'متوسط الدرجات',
                    'data' => $data,
                    'borderColor' => '#2563EB',
                    'backgroundColor' => 'rgba(37,99,235,0.1)',
                    'tension' => 0.4,
                    'fill' => true,
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
