<?php

namespace App\Filament\Widgets;

use App\Models\SupervisoryVisit;
use App\Services\DashboardKpiService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

/**
 * ويدجت توزيع مستويات الزيارات الإشرافية في لوحة Filament.
 *
 * Arabic: يحسب عدد الزيارات لكل مستوى (`overall_level`) ضمن النطاق والفترة المحددين
 * في فلاتر لوحة المؤشرات، ثم يعرض النتائج كمخطط Doughnut.
 * EN: Doughnut chart for supervisory-visit level distribution, driven by the dashboard page filters.
 */
class SupervisionLevelDistributionWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'توزيع مستويات الزيارات';

    protected static ?int $sort = 31;

    protected int|string|array $columnSpan = 'half';

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

        $centerIds = app(DashboardKpiService::class)
            ->centerIdsForScope($user, $scopeType, $scopeId ? (int) $scopeId : null);

        $counts = SupervisoryVisit::query()
            ->whereBetween(DB::raw('DATE(visited_at)'), [$dateFrom, $dateTo])
            ->whereIn('center_id', $centerIds)
            ->select('overall_level', DB::raw('count(*) as total'))
            ->groupBy('overall_level')
            ->pluck('total', 'overall_level');

        $levels = ['excellent', 'good', 'acceptable', 'weak'];
        $data = array_map(fn ($l) => (int) ($counts[$l] ?? 0), $levels);

        return [
            'datasets' => [
                [
                    'label' => 'عدد الزيارات',
                    'data' => $data,
                    'backgroundColor' => ['#0F6B4F', '#2E9B76', '#C08A2E', '#B4462F'],
                ],
            ],
            'labels' => ['ممتاز', 'جيد', 'مقبول', 'ضعيف'],
        ];
    }

    /**
     * نوع المخطط.
     * EN: Chart type.
     */
    protected function getType(): string
    {
        return 'doughnut';
    }
}
