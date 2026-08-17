<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceRecord;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

/**
 * ويدجت أعلى المستخدمين تسجيلاً لغياب الطلاب (هذا الشهر) في لوحة Filament.
 *
 * Arabic: يجمع عدد سجلات الغياب (مبرر/غير مبرر) حسب `recorded_by_user_id` خلال الشهر الحالي،
 * ثم يعرض أعلى 10 مستخدمين مع العدد.
 * EN: Table widget showing top users who recorded student absences this month.
 */
class TopAbsentTeachersWidget extends BaseWidget
{
    protected static ?string $heading = 'أكثر المستخدمين تسجيلاً لغياب الطلاب (هذا الشهر)';

    protected static ?int $sort = 44;

    protected int|string|array $columnSpan = 'full';

    /**
     * تعريف جدول الويدجت.
     * EN: Builds the widget table.
     */
    public function table(Table $table): Table
    {
        $stats = AttendanceRecord::query()
            ->select('recorded_by_user_id', DB::raw('COUNT(*) as absent_count'))
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->whereIn('status', [
                AttendanceRecord::STATUS_EXCUSED,
                AttendanceRecord::STATUS_UNEXCUSED,
            ])
            ->whereNotNull('recorded_by_user_id')
            ->groupBy('recorded_by_user_id')
            ->orderByDesc('absent_count')
            ->limit(10)
            ->get()
            ->keyBy('recorded_by_user_id');

        if ($stats->isEmpty()) {
            return $table
                ->query(User::query()->whereRaw('1 = 0'))
                ->columns([
                    Tables\Columns\TextColumn::make('name')->label('المستخدم'),
                ])
                ->paginated(false);
        }

        return $table
            ->query(
                User::query()
                    ->whereIn('id', $stats->keys()->all())
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('المستخدم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('absent_count')
                    ->label('عدد سجلات الغياب')
                    ->getStateUsing(fn (User $record) => $stats[$record->id]->absent_count ?? 0)
                    ->badge()
                    ->color('danger'),
            ])
            ->paginated(false);
    }
}
