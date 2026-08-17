<?php

namespace App\Filament\Pages\Reports;

use App\Models\SupervisoryVisit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * صفحة تقرير الزيارات الإشرافية في لوحة Filament.
 *
 * Arabic: تعرض جدول زيارات الموجّه مع فلاتر متعددة (تاريخ/مركز/حلقة/معلّم/مستوى/اعتماد)،
 * وتراعي نطاق صلاحيات المستخدم (غير SuperAdmin يقتصر على المراكز المُدارة). كما تدعم تصدير CSV
 * مع BOM لضمان ظهور العربية بشكل صحيح.
 * EN: Filament report page for supervisory visits with scoped query and CSV export.
 */
class SupervisionReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'تقارير الزيارات';
    protected static ?string $navigationGroup = 'التقارير';
    protected static ?string $title = 'تقرير زيارات الموجّه';
    protected static string $view = 'filament.pages.reports.supervision-report';

    public array $filters = [
        'date_from' => null,
        'date_to' => null,
        'center_id' => null,
        'halaqah_id' => null,
        'teacher_user_id' => null,
        'level' => null,
        'finalized' => null,
    ];

    public function mount(): void
    {
        $this->filters['date_from'] ??= now()->startOfMonth()->toDateString();
        $this->filters['date_to'] ??= now()->toDateString();
    }

    /**
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
     * نموذج الفلاتر (Filters Form).
     *
     * Arabic: يحدّد نطاق خيارات المراكز/الحلقات/المعلّمين حسب صلاحيات المستخدم.
     * EN: Live filters form with permission-aware options.
     */
    public function filtersForm(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->statePath('filters')
            ->live()
            ->schema([
            DatePicker::make('date_from')->label('من')->live(),
            DatePicker::make('date_to')->label('إلى')->live(),
            Select::make('center_id')
                ->label('المركز')
                ->options(fn () => \App\Models\Center::query()
                    ->when($user && ! $user->hasRole('SuperAdmin'), fn ($q) => $q->whereIn('id', $user->managedCenters()->pluck('id')))
                    ->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->live(),
            Select::make('halaqah_id')
                ->label('الحلقة')
                ->options(fn (callable $get) => \App\Models\Halaqah::query()
                    ->when($get('center_id'), fn ($q) => $q->where('center_id', $get('center_id')))
                    ->when($user && ! $user->hasRole('SuperAdmin'), fn ($q) => $q->whereIn('center_id', $user->managedCenters()->pluck('id')))
                    ->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->live(),
            Select::make('teacher_user_id')
                ->label('المعلّم')
                ->options(fn (callable $get) => \App\Models\TeacherProfile::query()
                    ->when($get('halaqah_id'), fn ($q) => $q->where('halaqah_id', $get('halaqah_id')))
                    ->when($user && ! $user->hasRole('SuperAdmin'), function ($q) use ($user) {
                        $q->whereHas('halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $user->managedCenters()->pluck('id')));
                    })
                    ->with('user:id,name')
                    ->get()
                    ->mapWithKeys(fn ($tp) => [$tp->user_id => $tp->user?->name ?? ('Teacher#' . $tp->user_id)])
                    ->all())
                ->searchable()
                ->live(),
            Select::make('level')
                ->label('المستوى')
                ->options([
                    'excellent' => 'excellent',
                    'good' => 'good',
                    'acceptable' => 'acceptable',
                    'weak' => 'weak',
                ])
                ->live(),
            Select::make('finalized')
                ->label('معتمدة؟')
                ->options(['1' => 'نعم', '0' => 'لا'])
                ->live(),
            ])
            ->columns(4);
    }

    /**
     * تعريف جدول التقرير.
     *
     * Arabic: يبني Query اعتماداً على الفلاتر الحالية مع تحميل العلاقات الأساسية لعرض البيانات.
     * EN: Builds the table query based on current filters and eager loads needed relations.
     */
    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                SupervisoryVisit::query()
                    ->with([
                        'center:id,name',
                        'halaqah:id,name,center_id',
                        'teacher:id,name',
                        'supervisor:id,name',
                    ])
                    ->when($user && ! $user->hasRole('SuperAdmin'), fn (Builder $q) => $q->whereIn('center_id', $user->managedCenters()->pluck('id')))
                    ->when($this->filters['center_id'], fn (Builder $q) => $q->where('center_id', $this->filters['center_id']))
                    ->when($this->filters['halaqah_id'], fn (Builder $q) => $q->where('halaqah_id', $this->filters['halaqah_id']))
                    ->when($this->filters['teacher_user_id'], fn (Builder $q) => $q->where('teacher_user_id', $this->filters['teacher_user_id']))
                    ->when($this->filters['level'], fn (Builder $q) => $q->where('overall_level', $this->filters['level']))
                    ->when($this->filters['finalized'] !== null && $this->filters['finalized'] !== '', fn (Builder $q) => $q->where('is_finalized', (int) $this->filters['finalized']))
                    ->when($this->filters['date_from'] && $this->filters['date_to'], fn (Builder $q) => $q->whereBetween(\DB::raw('DATE(visited_at)'), [$this->filters['date_from'], $this->filters['date_to']]))
            )
            ->columns([
                Tables\Columns\TextColumn::make('visited_at')->label('تاريخ الزيارة')->dateTime(),
                Tables\Columns\TextColumn::make('center.name')->label('المركز'),
                Tables\Columns\TextColumn::make('halaqah.name')->label('الحلقة'),
                Tables\Columns\TextColumn::make('teacher.name')->label('المعلّم'),
                Tables\Columns\TextColumn::make('overall_score')->label('الدرجة')->numeric(2),
                Tables\Columns\BadgeColumn::make('overall_level')->label('المستوى'),
                Tables\Columns\IconColumn::make('is_finalized')->label('معتمدة')->boolean(),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('تصدير CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => $this->exportCsv()),
            ])
            ->defaultSort('visited_at', 'desc')
            ->paginationPageOptions([25, 50, 100]);
    }

    /**
     * تصدير التقرير إلى CSV.
     *
     * Arabic: يحدّ العدد الأقصى للصفوف لحماية الأداء، ويضيف BOM لعرض العربية بشكل صحيح.
     * EN: Streams a UTF-8 CSV (with BOM) up to a max row limit.
     */
    public function exportCsv(): StreamedResponse
    {
        $maxRows = 5000;
        $filename = 'supervision_report_' . now()->format('Ymd_His') . '.csv';

        $query = $this->getFilteredSortedTableQuery()->clone()->limit($maxRows);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['visited_at', 'center', 'halaqah', 'teacher', 'overall_score', 'overall_level', 'finalized']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        optional($r->visited_at)->toDateTimeString(),
                        optional($r->center)->name,
                        optional($r->halaqah)->name,
                        optional($r->teacher)->name,
                        $r->overall_score,
                        $r->overall_level,
                        $r->is_finalized ? 1 : 0,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

