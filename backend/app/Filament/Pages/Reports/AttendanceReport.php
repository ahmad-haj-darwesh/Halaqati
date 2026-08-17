<?php

namespace App\Filament\Pages\Reports;

use App\Exports\MonthlyAttendanceExport;
use App\Models\AttendanceRecord;
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
 * صفحة تقرير الحضور في لوحة Filament.
 *
 * Arabic: تعرض جدول سجلات الحضور مع فلاتر (تاريخ/مركز/حلقة) وتراعي نطاق صلاحيات المستخدم
 * (SuperAdmin يرى الجميع، وغير ذلك يقتصر على المراكز المُدارة). كما تدعم التصدير إلى CSV
 * (مع BOM لضمان ظهور العربية بشكل صحيح) وإلى Excel عبر `MonthlyAttendanceExport`.
 * EN: Filament report page for attendance with filters, scoped query, and CSV/Excel export.
 */
class AttendanceReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'تقارير الحضور';
    protected static ?string $navigationGroup = 'التقارير';
    protected static ?string $title = 'تقرير الحضور';
    protected static string $view = 'filament.pages.reports.attendance-report';

    public array $filters = [
        'date_from' => null,
        'date_to' => null,
        'center_id' => null,
        'halaqah_id' => null,
    ];

    /**
     * تهيئة القيم الافتراضية للفلاتر عند فتح الصفحة.
     * EN: Initializes default filter values.
     */
    public function mount(): void
    {
        $this->filters['date_from'] ??= now()->startOfMonth()->toDateString();
        $this->filters['date_to'] ??= now()->toDateString();
    }

    /**
     * تسجيل نموذج باسم filtersForm حتى يعيده InteractsWithForms وليس Infolists.
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
     * نموذج الفلاتر (Filters Form).
     *
     * Arabic: يحدّث `filters` مباشرة (live) لتحديث الجدول تلقائياً.
     * EN: Live filters form that updates the table query.
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
                    ->when($user && ! $user->hasRole('SuperAdmin'), function ($q) use ($user) {
                        $q->whereIn('center_id', $user->managedCenters()->pluck('id'));
                    })
                    ->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->live(),
            ])
            ->columns(4);
    }

    /**
     * تعريف جدول التقرير.
     *
     * Arabic: يبني Query اعتماداً على الفلاتر الحالية مع تحميل العلاقات اللازمة.
     * EN: Builds the table query based on current filters.
     */
    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                AttendanceRecord::query()
                    ->with(['student:id,full_name', 'halaqah:id,name,center_id', 'halaqah.center:id,name'])
                    ->when($user && ! $user->hasRole('SuperAdmin'), function (Builder $q) use ($user) {
                        $q->whereHas('halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $user->managedCenters()->pluck('id')));
                    })
                    ->when($this->filters['center_id'], fn (Builder $q) => $q->whereHas('halaqah', fn (Builder $hq) => $hq->where('center_id', $this->filters['center_id'])))
                    ->when($this->filters['halaqah_id'], fn (Builder $q) => $q->where('halaqah_id', $this->filters['halaqah_id']))
                    ->when($this->filters['date_from'] && $this->filters['date_to'], fn (Builder $q) => $q->whereBetween('date', [$this->filters['date_from'], $this->filters['date_to']]))
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')->label('التاريخ')->date(),
                Tables\Columns\TextColumn::make('halaqah.center.name')->label('المركز')->toggleable(),
                Tables\Columns\TextColumn::make('halaqah.name')->label('الحلقة'),
                Tables\Columns\TextColumn::make('student.full_name')->label('الطالب'),
                Tables\Columns\BadgeColumn::make('status')->label('الحالة')
                    ->colors([
                        'success' => AttendanceRecord::STATUS_PRESENT,
                        'warning' => AttendanceRecord::STATUS_EXCUSED,
                        'danger' => AttendanceRecord::STATUS_UNEXCUSED,
                    ]),
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
            ->defaultSort('date', 'desc')
            ->paginationPageOptions([25, 50, 100]);
    }

    /**
     * تصدير التقرير إلى CSV.
     *
     * Arabic: يحدّ العدد الأقصى للصفوف لحماية الأداء، ويضيف BOM لضمان فتح UTF-8 بشكل صحيح.
     * EN: Streams a UTF-8 CSV (with BOM) up to a max row limit.
     */
    public function exportCsv(): StreamedResponse
    {
        $maxRows = 5000;
        $filename = 'attendance_report_' . now()->format('Ymd_His') . '.csv';

        $query = $this->getFilteredSortedTableQuery()->clone()->limit($maxRows);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            // BOM: يجبر Excel (وغيره) على فتح الملف كـ UTF-8 فيعرض العربية بشكل صحيح
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['date', 'center', 'halaqah', 'student', 'status']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->date,
                        optional($r->halaqah?->center)->name,
                        optional($r->halaqah)->name,
                        optional($r->student)->full_name,
                        $r->status,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * تصدير التقرير إلى Excel.
     *
     * Arabic: يعتمد على `MonthlyAttendanceExport` ويستخدم شهر/سنة من تاريخ البداية.
     * EN: Exports to Excel using MonthlyAttendanceExport.
     */
    public function exportExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $from = $this->filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $month = (int) \Carbon\Carbon::parse($from)->month;
        $year = (int) \Carbon\Carbon::parse($from)->year;
        $centerId = $this->filters['center_id'] ? (int) $this->filters['center_id'] : null;

        return Excel::download(
            new MonthlyAttendanceExport($month, $year, $centerId),
            'attendance-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.xlsx'
        );
    }
}

