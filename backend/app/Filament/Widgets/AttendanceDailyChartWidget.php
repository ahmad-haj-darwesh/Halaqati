<?php

namespace App\Filament\Widgets;

use App\Services\DashboardKpiService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * ويدجت مخطط الحضور اليومي في لوحة Filament.
 *
 * Arabic: يعرض سلسلة زمنية للحضور/الغياب (مبرر/غير مبرر) ضمن نطاق وتواريخ محددة من فلاتر الصفحة،
 * بالاعتماد على `DashboardKpiService`.
 * EN: Line chart widget for daily attendance series using DashboardKpiService and page filters.
 */
class AttendanceDailyChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'الحضور يوميًا';
    protected static ?int $sort = 10;

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

        $series = app(DashboardKpiService::class)->attendanceDailySeries(
            $scopeType,
            $scopeId ? (int) $scopeId : null,
            $dateFrom,
            $dateTo,
            $user,
        );

        return [
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'label' => 'حاضر',
                    'data' => $series['present'],
                    'borderColor' => '#16A34A',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.2)',
                    'fill' => true,
                ],
                [
                    'label' => 'غياب مبرر',
                    'data' => $series['excused'],
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'fill' => true,
                ],
                [
                    'label' => 'غياب غير مبرر',
                    'data' => $series['unexcused'],
                    'borderColor' => '#DC2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.2)',
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

