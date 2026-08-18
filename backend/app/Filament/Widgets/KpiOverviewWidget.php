<?php

namespace App\Filament\Widgets;

use App\Services\DashboardKpiService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * ويدجت مؤشرات الأداء (KPIs) في لوحة Filament.
 *
 * Arabic: يعرض إحصاءات مختصرة (طلاب نشطون/نسبة حضور/نسبة غياب غير مبرر) وفق فلاتر الصفحة
 * (النطاق والتاريخ) باستخدام `DashboardKpiService`، ويضبط اللون بناءً على نسب الأداء.
 * EN: Stats overview widget powered by DashboardKpiService, respecting page filters.
 */
class KpiOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    use InteractsWithPageFilters;

    /**
     * بناء عناصر الإحصاءات المعروضة.
     * EN: Builds stats cards.
     */
    protected function getStats(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
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

        $presentPct = (float) ($kpis['attendance']['present_percent'] ?? 0);
        $unexcusedPct = (float) ($kpis['attendance']['unexcused_percent'] ?? 0);

        return [
            Stat::make('الطلاب النشطون', (string) ($kpis['active_students'] ?? 0))
                ->description('حسب تسجيلات (Enrollment) النشطة')
                ->color('primary'),

            Stat::make('نسبة الحضور', $presentPct . '%')
                ->description('خلال الفترة المحددة')
                ->color($presentPct >= 85 ? 'success' : ($presentPct >= 70 ? 'warning' : 'danger')),

            Stat::make('غياب غير مبرر', $unexcusedPct . '%')
                ->description('كلما أقل كان أفضل')
                ->color($unexcusedPct <= 5 ? 'success' : ($unexcusedPct <= 15 ? 'warning' : 'danger')),
        ];
    }
}

