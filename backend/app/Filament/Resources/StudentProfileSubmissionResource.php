<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentProfileSubmissionResource\Pages;
use App\Models\StudentProfileSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * مورد Filament لمراجعة طلبات ملفات الطلاب.
 *
 * Arabic: يسمح للإدارة/المشرفين بعرض طلبات المراجعة (Submission) واتخاذ قرار
 * (قبول/رفض) مع تنفيذ منطق الاعتماد عبر `StudentProfileSubmissionService`.
 * EN: Filament resource for reviewing student profile submissions.
 */
class StudentProfileSubmissionResource extends Resource
{
    protected static ?string $model = StudentProfileSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'مراجعة ملفات الطلاب';

    protected static ?string $modelLabel = 'طلب مراجعة';

    protected static ?string $pluralModelLabel = 'طلبات مراجعة الملفات';

    protected static ?string $navigationGroup = 'الطلاب';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    /**
     * جدول العرض في Filament (قائمة الطلبات والإجراءات).
     *
     * Arabic: يوفّر أعمدة أساسية، فلتر حسب الحالة، وإجراءات قبول/رفض مع صلاحيات Gate.
     * EN: Configures the table columns/filters/actions for submissions.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('الطالب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلّم'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم في الطلب')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        StudentProfileSubmission::STATUS_PENDING  => 'قيد المراجعة',
                        StudentProfileSubmission::STATUS_APPROVED => 'مقبول',
                        StudentProfileSubmission::STATUS_REJECTED => 'مرفوض',
                        StudentProfileSubmission::STATUS_DRAFT    => 'مسودّة',
                        default => $state,
                    })
                    ->colors([
                        'warning' => StudentProfileSubmission::STATUS_PENDING,
                        'success' => StudentProfileSubmission::STATUS_APPROVED,
                        'danger'  => StudentProfileSubmission::STATUS_REJECTED,
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإرسال')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer_note')
                    ->label('ملاحظة المراجعة')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        StudentProfileSubmission::STATUS_PENDING   => 'قيد المراجعة',
                        StudentProfileSubmission::STATUS_APPROVED    => 'مقبول',
                        StudentProfileSubmission::STATUS_REJECTED    => 'مرفوض',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('changes')
                    ->label('عرض التغييرات')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('gray')
                    ->modalHeading('مقارنة القيم')
                    ->modalDescription(fn (StudentProfileSubmission $r) => 'الطالب: '.($r->student?->full_name ?? '—'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (StudentProfileSubmission $r) => view(
                        'filament.resources.student-profile-submission.changes',
                        ['rows' => self::diffRows($r)],
                    )),
                Tables\Actions\Action::make('approve')
                    ->label('قبول واعتماد الملف')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StudentProfileSubmission $r) => $r->status === StudentProfileSubmission::STATUS_PENDING
                        && Gate::allows('approve', $r))
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد اعتماد ملف الطالب')
                    ->modalDescription('سيتم نسخ البيانات إلى ملف الطالب ولن يستطيع المعلّم التعديل حتى تسمح له يدوياً.')
                    ->action(function (StudentProfileSubmission $record) {
                        Gate::authorize('approve', $record);
                        app(\App\Services\StudentProfileSubmissionService::class)
                            ->approve($record, auth()->user());
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (StudentProfileSubmission $r) => $r->status === StudentProfileSubmission::STATUS_PENDING
                        && Gate::allows('reject', $r))
                    ->form([
                        Forms\Components\Textarea::make('reviewer_note')
                            ->label('سبب الرفض / ملاحظة للمعلّم')
                            ->required(),
                    ])
                    ->action(function (StudentProfileSubmission $record, array $data) {
                        Gate::authorize('reject', $record);
                        app(\App\Services\StudentProfileSubmissionService::class)
                            ->reject($record, auth()->user(), $data['reviewer_note'] ?? null);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentProfileSubmissions::route('/'),
        ];
    }

    /**
     * بناء صفوف المقارنة بين القيمة الحالية المعتمدة والقيمة المقترحة.
     *
     * Arabic: التعديلات لا تُطبّق على الطالب قبل الاعتماد، لذا يعرض هذا الجدول
     * فرقاً حقيقياً يمكّن المشرف من القرار عن بيّنة.
     * EN: Builds the current-vs-proposed diff rows for the reviewer modal.
     *
     * @return array<int, array{label:string, current:string, proposed:string, changed:bool}>
     */
    public static function diffRows(StudentProfileSubmission $submission): array
    {
        $student = $submission->student;

        $fields = [
            'full_name'      => 'الاسم الكامل',
            'gender'         => 'الجنس',
            'birth_date'     => 'تاريخ الميلاد',
            'guardian_name'  => 'اسم ولي الأمر',
            'guardian_phone' => 'هاتف ولي الأمر',
            'national_id'    => 'الهوية الوطنية',
            'notes'          => 'ملاحظات',
            'photo_path'     => 'الصورة',
        ];

        $format = static function (string $key, mixed $value): string {
            if ($value === null || $value === '') {
                return '—';
            }

            return match ($key) {
                'gender' => $value === 'male' ? 'ذكر' : 'أنثى',
                'birth_date' => $value instanceof \DateTimeInterface
                    ? $value->format('Y/m/d')
                    : (string) $value,
                'photo_path' => 'صورة مرفوعة',
                default => (string) $value,
            };
        };

        $rows = [];

        foreach ($fields as $key => $label) {
            $current = $student?->{$key};
            $proposed = $submission->{$key};

            $currentText = $format($key, $current);
            $proposedText = $format($key, $proposed);

            $rows[] = [
                'label'    => $label,
                'current'  => $currentText,
                'proposed' => $proposedText,
                'changed'  => $currentText !== $proposedText,
            ];
        }

        return $rows;
    }

    /**
     * تقييد الاستعلام حسب دور المستخدم في لوحة الإدارة.
     *
     * Arabic: SuperAdmin يرى كل الطلبات، وAdmin يرى طلبات الطلاب ضمن مراكزه المدارة.
     * EN: Scopes the query based on the authenticated user's roles/managed centers.
     */
    public static function getEloquentQuery(): Builder
    {
        // المسودّات ما زالت تحت يد المعلّم ولم تُرسل للمراجعة — لا تظهر للمشرف.
        $query = parent::getEloquentQuery()
            ->with(['student', 'teacher', 'reviewer'])
            ->where('status', '!=', StudentProfileSubmission::STATUS_DRAFT);

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        if ($user->hasRole('Admin')) {
            $centerIds = $user->managedCenters()->pluck('id');

            return $query->whereHas('student.enrollments.halaqah', fn (Builder $q) => $q->whereIn('center_id', $centerIds));
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * هل يمكن عرض هذا المورد في القائمة؟
     * EN: Whether the current user can view any records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', StudentProfileSubmission::class) ?? false;
    }

    /**
     * منع إنشاء طلبات يدوياً من لوحة Filament (المصدر هو تطبيق الموبايل).
     * EN: Disables creation from admin panel; submissions come from mobile.
     */
    public static function canCreate(): bool
    {
        return false;
    }
}
