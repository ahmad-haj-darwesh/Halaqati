<?php

namespace App\Filament\Widgets;

use App\Services\DashboardKpiService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/**
 * ويدجت جدول "أكثر الأسباب" في لوحة Filament.
 *
 * Arabic: يعرض أكثر أسباب التميّز/التقصير بحسب نطاق وتواريخ محددة من فلاتر الصفحة،
 * ويستند إلى `DashboardKpiService` التي تُرجع `top_reasons`.
 * EN: Widget showing top reasons table powered by DashboardKpiService and page filters.
 */
class TopReasonsTableWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'أكثر أسباب التميّز/التقصير';
    protected static ?int $sort = 14;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.top-reasons-table';

    /**
     * @return array<int,array{label:string,type:string,total:int}>
     */
    public function rows(): array
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

        return collect($kpis['top_reasons'] ?? [])
            ->map(fn ($r) => [
                'label' => (string) $r->label,
                'type' => (string) $r->type,
                'total' => (int) $r->total,
            ])
            ->all();
    }
}

