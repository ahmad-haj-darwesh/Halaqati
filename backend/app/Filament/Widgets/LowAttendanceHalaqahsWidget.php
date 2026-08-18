<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceRecord;
use App\Models\Halaqah;
use Filament\Widgets\ChartWidget;

/**
 * ويدجت أقل الحلقات حضوراً (آخر 7 أيام) في لوحة Filament.
 *
 * Arabic: يحسب نسبة الحضور لكل حلقة خلال آخر 7 أيام ثم يعرض أقل 8 حلقات حسب النسبة.
 * ملاحظة: هذا الويدجت لا يطبّق نطاق صلاحيات المستخدم حالياً (يعرض من كل الحلقات).
 * EN: Bar chart showing the lowest attendance-rate halaqahs in the last 7 days.
 */
class LowAttendanceHalaqahsWidget extends ChartWidget
{
    protected static ?string $heading = 'أقل الحلقات حضوراً (آخر 7 أيام)';

    protected static ?int $sort = 12;

    protected int|string|array $columnSpan = 'half';

    /**
     * بيانات المخطط (Labels/Datasets).
     * EN: Chart data.
     */
    protected function getData(): array
    {
        $from = now()->subDays(7)->toDateString();

        $halaqahs = Halaqah::query()
            ->orderBy('name')
            ->limit(40)
            ->get()
            ->map(function (Halaqah $h) use ($from) {
                $records = AttendanceRecord::query()
                    ->where('halaqah_id', $h->id)
                    ->where('date', '>=', $from)
                    ->get();

                $total = $records->count();
                $present = $records->where('status', AttendanceRecord::STATUS_PRESENT)->count();
                $rate = $total > 0 ? (int) round(($present / $total) * 100) : 0;

                return ['name' => $h->name, 'rate' => $rate];
            })
            ->sortBy('rate')
            ->take(8)
            ->values();

        return [
            'labels' => $halaqahs->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'نسبة الحضور %',
                    'data' => $halaqahs->pluck('rate')->toArray(),
                    'backgroundColor' => $halaqahs->map(fn ($h) => match (true) {
                        $h['rate'] >= 80 => '#0F6B4F',
                        $h['rate'] >= 60 => '#2E9B76',
                        $h['rate'] >= 40 => '#C08A2E',
                        default => '#B4462F',
                    })->toArray(),
                    'borderRadius' => 8,
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
