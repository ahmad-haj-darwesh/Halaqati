<?php

namespace App\Filament\Widgets;

use App\Services\DashboardKpiService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * ويدجت توزيع مستويات نتائج الاختبارات في لوحة Filament.
 *
 * Arabic: يعرض توزيع المستويات (excellent/good/acceptable/weak) ضمن نطاق وتواريخ محددة
 * من فلاتر الصفحة عبر `DashboardKpiService`.
 * EN: Bar chart for test level distribution powered by DashboardKpiService and page filters.
 */
class TestLevelsDistributionChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'توزيع مستويات الاختبارات';
    protected static ?int $sort = 20;

    /**
     * بيانات المخطط (Labels/Datasets).
     * EN: Chart data.
     */
    protected function getData(): array
    {
        $user = auth()->user();
        if (! $user) {
            return ['datasets' => [], 'labels' => []];
        }

        $scopeType = (string) ($this->filters['scope_type'] ?? 'all');
        $scopeId = $this->filters['scope_id'] ?? null;
        $dateFrom = (string) ($this->filters['date_from'] ?? now()->startOfMonth()->toDateString());
        $dateTo = (string) ($this->filters['date_to'] ?? now()->toDateString());

        $kpis = app(DashboardKpiService::class)->kpisByScope(
            $scopeType,
            $scopeId ? (int) $scopeId : null,
            $dateFrom,
            $dateTo,
            $user,
        );

        $levels = $kpis['tests']['levels'] ?? [];

        return [
            'labels' => ['Excellent', 'Good', 'Acceptable', 'Weak'],
            'datasets' => [
                [
                    'label' => 'النتائج',
                    'data' => [
                        (int) ($levels['excellent'] ?? 0),
                        (int) ($levels['good'] ?? 0),
                        (int) ($levels['acceptable'] ?? 0),
                        (int) ($levels['weak'] ?? 0),
                    ],
                    'backgroundColor' => ['#0F6B4F', '#2E9B76', '#C08A2E', '#B4462F'],
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
        return 'bar';
    }
}

