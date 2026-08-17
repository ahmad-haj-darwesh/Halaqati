<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Support\Carbon;

/**
 * لوحة المؤشرات الرئيسية في Filament.
 *
 * Arabic: Dashboard مخصص يدعم نموذج فلاتر (نطاق + فترة) ثم يعرض Widgets تعتمد
 * على هذه الفلاتر (KPIs/Charts/Tables).
 * EN: Filament dashboard page with filter form and scoped widgets.
 */
class AdminDashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationLabel = 'لوحة المؤشرات';
    protected static ?string $title = 'لوحة المؤشرات';

    /**
     * Filament يبحث عن نماذج عبر getForms()؛ trait HasFiltersForm يعرّف getHasFiltersForms()
     * لكن الاكتشاف التلقائي يتوقع اسمًا مختلفًا، لذلك نسجّل filtersForm صراحةً.
     *
     * @return array<int, string>
     */
    protected function getForms(): array
    {
        return [
            'form',
            'filtersForm',
        ];
    }

    /**
     * نموذج الفلاتر الخاص بالـ Dashboard.
     *
     * Arabic: يسمح باختيار نطاق التقرير (كل/منطقة/مركز/حلقة) وفترة التاريخ.
     * EN: Dashboard filters form (scope + date range).
     */
    public function filtersForm(Form $form): Form
    {
        $today = Carbon::today();
        $from = $today->copy()->startOfMonth()->toDateString();
        $to = $today->toDateString();

        return $form
            ->statePath('filters')
            ->live()
            ->schema([
            Select::make('scope_type')
                ->label('النطاق')
                ->options([
                    'all' => 'الكل',
                    'region' => 'منطقة',
                    'center' => 'مركز',
                    'halaqah' => 'حلقة',
                ])
                ->default('all')
                ->live(),
            Select::make('scope_id')
                ->label('المعرّف')
                ->helperText('اختر المنطقة/المركز/الحلقة حسب النطاق')
                ->options(fn (callable $get) => $this->scopeOptions($get('scope_type')))
                ->searchable()
                ->visible(fn (callable $get) => in_array($get('scope_type'), ['region', 'center', 'halaqah'], true))
                ->live(),
            DatePicker::make('date_from')->label('من')->default($from)->live(),
            DatePicker::make('date_to')->label('إلى')->default($to)->live(),
            ])
            ->columns(4);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\KpiOverviewWidget::class,
        ];
    }

    /**
     * Widgets المعروضة داخل الـ Dashboard.
     *
     * Arabic: تُستخدم مع فلاتر الصفحة (filters) لتجميع البيانات حسب النطاق/التاريخ.
     * EN: Widgets displayed on the dashboard (driven by filters).
     */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\AttendanceDailyChartWidget::class,
            \App\Filament\Widgets\TestLevelsDistributionChartWidget::class,
            \App\Filament\Widgets\TopReasonsTableWidget::class,
            \App\Filament\Widgets\MonthlyTestScoresWidget::class,
            \App\Filament\Widgets\LowAttendanceHalaqahsWidget::class,
            \App\Filament\Widgets\VisitScoresComparisonWidget::class,
            \App\Filament\Widgets\TopAbsentTeachersWidget::class,
        ];
    }

    /**
     * @return array<int|string,string>
     */
    private function scopeOptions(?string $scopeType): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        return match ($scopeType) {
            'region' => \App\Models\Region::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            'center' => \App\Models\Center::query()
                ->when(! $user->hasRole('SuperAdmin'), fn ($q) => $q->whereIn('id', $user->managedCenters()->pluck('id')))
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            'halaqah' => \App\Models\Halaqah::query()
                ->when(! $user->hasRole('SuperAdmin'), function ($q) use ($user) {
                    $centerIds = $user->managedCenters()->pluck('id');
                    $q->whereIn('center_id', $centerIds);
                })
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            default => [],
        };
    }
}

