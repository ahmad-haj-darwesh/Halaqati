<?php

namespace App\Filament\Widgets;

use App\Models\Center;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\TeacherProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * ودجت إحصاءات سريعة للوحة Filament.
 *
 * Arabic: تعرض عدادات إجمالية (مناطق/مراكز/حلقات/معلمين) لـ SuperAdmin، أو ضمن
 * مراكز المستخدم المُدارة لباقي الأدوار.
 * EN: Quick stats widget for Filament, scoped by user role/managed centers.
 */
class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * بناء قائمة الإحصاءات (Stat cards).
     * EN: Returns Stat cards for the overview widget.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user->hasRole('SuperAdmin')) {
            return [
                Stat::make('المناطق', Region::count())
                    ->description('إجمالي المناطق')
                    ->icon('heroicon-o-map')
                    ->color('primary'),
                Stat::make('المراكز', Center::count())
                    ->description('إجمالي المراكز')
                    ->icon('heroicon-o-building-library')
                    ->color('success'),
                Stat::make('الحلقات', Halaqah::count())
                    ->description('إجمالي الحلقات')
                    ->icon('heroicon-o-book-open')
                    ->color('warning'),
                Stat::make('المعلمون', TeacherProfile::count())
                    ->description('إجمالي المعلمين')
                    ->icon('heroicon-o-academic-cap')
                    ->color('danger'),
            ];
        }

        $centerIds = $user->managedCenters()->pluck('id');

        return [
            Stat::make('مراكزي', $user->managedCenters()->count())
                ->description('المراكز تحت إشرافك')
                ->icon('heroicon-o-building-library')
                ->color('success'),
            Stat::make('الحلقات', Halaqah::whereIn('center_id', $centerIds)->count())
                ->description('حلقات ضمن مراكزك')
                ->icon('heroicon-o-book-open')
                ->color('warning'),
            Stat::make('المعلمون', TeacherProfile::whereHas('halaqah', fn ($q) => $q->whereIn('center_id', $centerIds))->count())
                ->description('معلمون ضمن مراكزك')
                ->icon('heroicon-o-academic-cap')
                ->color('danger'),
        ];
    }
}
