<?php

namespace App\Filament\Widgets;

use App\Models\SupervisoryVisit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * ويدجت توزيع مستويات الزيارات (آخر 30 يوم) في لوحة Filament.
 *
 * Arabic: يحسب عدد الزيارات لكل مستوى (`overall_level`) خلال آخر 30 يوماً، مع تقييد النطاق
 * حسب المراكز المُدارة لغير SuperAdmin، ثم يعرض النتائج كمخطط Doughnut.
 * EN: Doughnut chart widget for supervisory-visit level distribution over last 30 days, scoped by managed centers.
 */
class SupervisionLevelDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'توزيع مستويات الزيارات (آخر 30 يوم)';

    protected static ?int $sort = 2;

    /**
     * بيانات المخطط (Labels/Datasets).
     * EN: Chart data.
     */
    protected function getData(): array
    {
        $user = auth()->user();
        $query = SupervisoryVisit::query()
            ->where('visited_at', '>=', now()->subDays(30));

        if ($user && ! $user->hasRole('SuperAdmin')) {
            $centerIds = $user->managedCenters()->pluck('id');
            $query->whereIn('center_id', $centerIds);
        }

        $counts = $query
            ->select('overall_level', DB::raw('count(*) as total'))
            ->groupBy('overall_level')
            ->pluck('total', 'overall_level');

        $labels = ['excellent', 'good', 'acceptable', 'weak'];
        $data = array_map(fn ($l) => (int) ($counts[$l] ?? 0), $labels);

        return [
            'datasets' => [
                [
                    'label' => 'Visits',
                    'data' => $data,
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

