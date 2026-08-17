<?php

namespace App\Filament\Pages\Reports;

use App\Exports\TestResultsExport;
use App\Models\Test;
use App\Models\TestResult;
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
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * صفحة تقرير الاختبارات في لوحة Filament.
 *
 * Arabic: تعرض نتائج الاختبارات (TestResult) مع فلاتر (تاريخ/اختبار/مركز/حلقة/مستوى)
 * وتراعي نطاق صلاحيات المستخدم (غير SuperAdmin يقتصر على المراكز المُدارة). كما تدعم:
 * - تصدير CSV مع BOM لضمان ظهور العربية صحيحًا.\n+ * - تصدير Excel عبر `TestResultsExport`.
 * EN: Filament report page for test results with filters, scoped query, and CSV/Excel exports.
 */
class TestsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'تقارير الاختبارات';
    protected static ?string $navigationGroup = 'التقارير';
    protected static ?string $title = 'تقرير الاختبارات';
    protected static string $view = 'filament.pages.reports.tests-report';

    public array $filters = [
        'date_from' => null,
        'date_to' => null,
        'test_id' => null,
        'center_id' => null,
        'halaqah_id' => null,
        'level' => null,
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
     * Arabic: يحدّد خيارات الاختبارات/المراكز/الحلقات حسب صلاحيات المستخدم.
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
            Select::make('test_id')
                ->label('الاختبار')
                ->options(fn () => Test::query()
                    ->when($user && ! $user->hasRole('SuperAdmin'), fn ($q) => $q->whereIn('scope_center_id', $user->managedCenters()->pluck('id')))
                    ->orderByDesc('id')
                    ->pluck('title', 'id')->all())
                ->searchable()
                ->live(),
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
            Select::make('level')
                ->label('المستوى')
                ->options([
                    'excellent' => 'excellent',
                    'good' => 'good',
                    'acceptable' => 'acceptable',
                    'weak' => 'weak',
                ])
                ->live(),
            ])
            ->columns(4);
    }

    /**
     * تعريف جدول التقرير.
     *
     * Arabic: يبني Query على `TestResult` مع تحميل العلاقات اللازمة لإظهار السياق.
     * EN: Builds the table query for test results based on current filters.
     */
    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                TestResult::query()
                    ->with([
                        'assignment.test:id,title,type,scope_center_id',
                        'assignment.halaqah:id,name,center_id',
                        'assignment.halaqah.center:id,name',
                        'assignment.student:id,full_name',
                    ])
                    ->when($user && ! $user->hasRole('SuperAdmin'), function (Builder $q) use ($user) {
                        $q->whereHas('assignment.halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $user->managedCenters()->pluck('id')));
                    })
                    ->when($this->filters['test_id'], fn (Builder $q) => $q->whereHas('assignment', fn (Builder $a) => $a->where('test_id', $this->filters['test_id'])))
                    ->when($this->filters['center_id'], fn (Builder $q) => $q->whereHas('assignment.halaqah', fn (Builder $hq) => $hq->where('center_id', $this->filters['center_id'])))
                    ->when($this->filters['halaqah_id'], fn (Builder $q) => $q->whereHas('assignment', fn (Builder $a) => $a->where('halaqah_id', $this->filters['halaqah_id'])))
                    ->when($this->filters['level'], fn (Builder $q) => $q->where('level', $this->filters['level']))
                    ->when($this->filters['date_from'] && $this->filters['date_to'], fn (Builder $q) => $q->whereBetween(\DB::raw('DATE(tested_at)'), [$this->filters['date_from'], $this->filters['date_to']]))
            )
            ->columns([
                Tables\Columns\TextColumn::make('tested_at')->label('تاريخ الاختبار')->dateTime(),
                Tables\Columns\TextColumn::make('assignment.test.title')->label('الاختبار')->wrap(),
                Tables\Columns\TextColumn::make('assignment.halaqah.center.name')->label('المركز')->toggleable(),
                Tables\Columns\TextColumn::make('assignment.halaqah.name')->label('الحلقة'),
                Tables\Columns\TextColumn::make('assignment.student.full_name')->label('الطالب'),
                Tables\Columns\TextColumn::make('total_score')->label('الدرجة')->numeric(2),
                Tables\Columns\BadgeColumn::make('level')->label('المستوى'),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('تصدير CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => $this->exportCsv()),
                Action::make('export_excel')
                    ->label('تصدير Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn () => $this->exportExcel()),
            ])
            ->defaultSort('tested_at', 'desc')
            ->paginationPageOptions([25, 50, 100]);
    }

    /**
     * تصدير التقرير إلى CSV.
     *
     * Arabic: يحدّ العدد الأقصى للصفوف لحماية الأداء، ويضيف BOM لضمان UTF-8.
     * EN: Streams a UTF-8 CSV (with BOM) up to a max row limit.
     */
    public function exportCsv(): StreamedResponse
    {
        $maxRows = 5000;
        $filename = 'tests_report_' . now()->format('Ymd_His') . '.csv';

        $query = $this->getFilteredSortedTableQuery()->clone()->limit($maxRows);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['tested_at', 'test', 'center', 'halaqah', 'student', 'total_score', 'level']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        optional($r->tested_at)->toDateTimeString(),
                        optional($r->assignment?->test)->title,
                        optional($r->assignment?->halaqah?->center)->name,
                        optional($r->assignment?->halaqah)->name,
                        optional($r->assignment?->student)->full_name,
                        $r->total_score,
                        $r->level,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * تصدير التقرير إلى Excel.
     *
     * Arabic: يمرّر `test_id` (إن وُجد) إلى `TestResultsExport`.
     * EN: Exports to Excel using TestResultsExport.
     */
    public function exportExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $testId = $this->filters['test_id'] ? (int) $this->filters['test_id'] : null;

        return Excel::download(
            new TestResultsExport($testId),
            'test-results-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}

