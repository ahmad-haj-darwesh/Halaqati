<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * صفحة سجل الأحداث (Activity Log) في Filament.
 *
 * Arabic: تعرض أحدث الأنشطة المسجلة عبر Spatie Activitylog. متاحة فقط لـ SuperAdmin
 * لتقليل مخاطر الاطلاع على نشاط النظام من غير المخولين.
 * EN: Filament page listing recent system activities (SuperAdmin only).
 */
class ActivityLogPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'سجل الأحداث';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'النظام';

    protected static ?string $title = 'سجل الأحداث';

    protected static string $view = 'filament.pages.activity-log';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('SuperAdmin') ?? false;
    }

    /**
     * بناء جدول Filament للأنشطة.
     *
     * Arabic: يتيح فلترة حسب المستخدم (causer) ويعرض وصف الحدث والوقت ونوع النموذج.
     * EN: Builds the table query/columns/filters for activity entries.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest())
            ->columns([
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('المستخدم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('الحدث')
                    ->wrap(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('النموذج')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('الوقت')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('المستخدم')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q) => $q->where('causer_type', User::class)->where('causer_id', $data['value'])
                        );
                    }),
            ]);
    }
}
