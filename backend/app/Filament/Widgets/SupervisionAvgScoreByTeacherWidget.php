<?php

namespace App\Filament\Widgets;

use App\Models\SupervisoryVisit;
use App\Services\DashboardKpiService;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * ويدجت متوسط درجات الزيارات حسب المعلّم في لوحة Filament.
 *
 * Arabic: يبني استعلاماً مُجمّعاً (AVG/COUNT) على `SupervisoryVisit` ضمن النطاق والفترة
 * المحددين في فلاتر لوحة المؤشرات.
 * EN: Table widget for average supervisory-visit scores per teacher, driven by the dashboard page filters.
 */
class SupervisionAvgScoreByTeacherWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'متوسط الدرجات حسب المعلم';

    protected static ?int $sort = 32;

    protected int|string|array $columnSpan = 'half';

    /**
     * الاستعلام الأساسي للجدول.
     * EN: Base query for the widget table.
     */
    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return SupervisoryVisit::query()->whereRaw('1 = 0');
        }

        $scopeType = (string) ($this->filters['scope_type'] ?? 'all');
        $scopeId = $this->filters['scope_id'] ?? null;
        $dateFrom = (string) ($this->filters['date_from'] ?? now()->startOfMonth()->toDateString());
        $dateTo = (string) ($this->filters['date_to'] ?? now()->toDateString());

        $centerIds = app(DashboardKpiService::class)
            ->centerIdsForScope($user, $scopeType, $scopeId ? (int) $scopeId : null);

        return SupervisoryVisit::query()
            ->whereBetween(DB::raw('DATE(visited_at)'), [$dateFrom, $dateTo])
            ->whereIn('center_id', $centerIds)
            ->whereNotNull('overall_score')
            ->select('teacher_user_id', DB::raw('AVG(overall_score) as avg_score'), DB::raw('COUNT(*) as visits_count'))
            ->groupBy('teacher_user_id')
            ->orderByDesc('avg_score');
    }

    /**
     * أعمدة الجدول.
     *
     * Arabic: يجلب اسم المعلّم عبر `teacher_user_id` لكون الاستعلام مُجمّعاً ولا يعيد علاقة `teacher` مباشرة.
     * EN: Table columns; teacher name is resolved from teacher_user_id due to aggregated query.
     *
     * @return array<int, \Filament\Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('teacher.name')
                ->label('المعلم')
                ->getStateUsing(fn ($record) => optional(\App\Models\User::find($record->teacher_user_id))->name),
            TextColumn::make('avg_score')
                ->label('المتوسط')
                ->numeric(2)
                ->badge()
                ->color(fn ($state) => match (true) {
                    $state >= 90 => 'success',
                    $state >= 70 => 'warning',
                    default => 'danger',
                }),
            TextColumn::make('visits_count')->label('عدد الزيارات'),
        ];
    }

    /**
     * نص الحالة الفارغة.
     * EN: Empty-state copy.
     */
    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد زيارات مُقيَّمة في هذه الفترة';
    }
}
