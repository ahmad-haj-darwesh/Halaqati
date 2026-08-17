<?php

namespace App\Filament\Widgets;

use App\Models\SupervisoryVisit;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * ويدجت متوسط درجات الزيارات حسب المعلّم (آخر 30 يوم) في لوحة Filament.
 *
 * Arabic: يبني استعلاماً مُجمّعاً (AVG/COUNT) على `SupervisoryVisit` خلال آخر 30 يوماً،
 * مع تقييد النطاق حسب المراكز المُدارة لغير SuperAdmin.
 * EN: Table widget showing average supervisory-visit scores per teacher for the last 30 days, scoped by managed centers.
 */
class SupervisionAvgScoreByTeacherWidget extends BaseWidget
{
    protected static ?string $heading = 'متوسط الدرجات حسب المعلم (آخر 30 يوم)';

    protected static ?int $sort = 3;

    /**
     * الاستعلام الأساسي للجدول.
     * EN: Base query for the widget table.
     */
    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        $query = SupervisoryVisit::query()
            ->where('visited_at', '>=', now()->subDays(30))
            ->whereNotNull('overall_score')
            ->select('teacher_user_id', DB::raw('AVG(overall_score) as avg_score'), DB::raw('COUNT(*) as visits_count'))
            ->groupBy('teacher_user_id')
            ->orderByDesc('avg_score');

        if ($user && ! $user->hasRole('SuperAdmin')) {
            $centerIds = $user->managedCenters()->pluck('id');
            $query->whereIn('center_id', $centerIds);
        }

        return $query;
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
            TextColumn::make('avg_score')->label('المتوسط')->numeric(2),
            TextColumn::make('visits_count')->label('عدد الزيارات'),
        ];
    }
}

