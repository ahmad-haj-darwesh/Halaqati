<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * مورد Filament لإدارة الطلاب (Students).
 *
 * Arabic: يوفّر نموذجاً شاملاً لبيانات الطالب وولي الأمر والصورة وصلاحيات المعلّم،
 * بالإضافة إلى جدول عرض مع فلاتر متعددة وإجراءات مخصصة (السماح للمعلّم بالتعديل،
 * نقل طالب، إيقاف/إنهاء). كما يقيّد الاستعلام حسب سياسة الصلاحيات.
 * EN: Filament resource for managing students with rich form, table filters, custom actions, and policy-based scoping.
 */
class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'الطلاب';
    protected static ?string $modelLabel = 'طالب';
    protected static ?string $pluralModelLabel = 'الطلاب';
    protected static ?int $navigationSort = 6;

    /**
     * نموذج إنشاء/تعديل الطالب.
     *
     * Arabic: يجمّع الحقول في Sections ويقدّم:
     * - بيانات الطالب الأساسية\n+     * - بيانات ولي الأمر\n+     * - ملاحظات\n+     * - الصورة + حالة قفل الملف + خيار السماح للمعلّم بالتعديل
     *
     * EN: Student create/edit form schema organized into sections.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('بيانات الطالب')
                    ->columns(2)
                    ->schema([
                        TextInput::make('full_name')
                            ->label('الاسم الكامل')
                            ->required()
                            ->maxLength(255),
                        Select::make('gender')
                            ->label('الجنس')
                            ->required()
                            ->options([
                                'male' => 'ذكر',
                                'female' => 'أنثى',
                            ]),
                        DatePicker::make('birth_date')
                            ->label('تاريخ الميلاد')
                            ->native(false),
                        Toggle::make('is_active')
                            ->label('مفعّل')
                            ->default(true),
                    ]),

                Section::make('بيانات ولي الأمر')
                    ->columns(2)
                    ->schema([
                        TextInput::make('guardian_name')->label('اسم ولي الأمر')->maxLength(255),
                        TextInput::make('guardian_phone')->label('هاتف ولي الأمر')->tel()->maxLength(50),
                        TextInput::make('national_id')->label('الهوية الوطنية')->maxLength(50),
                    ]),

                Section::make('ملاحظات')
                    ->schema([
                        Textarea::make('notes')->label('ملاحظات')->rows(4),
                    ]),

                Section::make('الصورة وصلاحيات المعلّم')
                    ->description('بعد اعتماد الملف من «مراجعة ملفات الطلاب» يُقفل الملف حتى تسمح للمعلّم بالتعديل.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('صورة الطالب')
                            ->image()
                            ->disk('public')
                            ->directory('student-photos')
                            ->visibility('public')
                            ->imageEditor()
                            ->columnSpanFull(),
                        Placeholder::make('profile_locked_display')
                            ->label('حالة اعتماد الملف')
                            ->content(function (?Student $record): string {
                                if ($record === null || ! $record->exists) {
                                    return 'غير معتمد بعد';
                                }

                                return $record->profile_locked
                                    ? 'معتمد ومقفل على المعلّم (حتى تسمح له بالتعديل)'
                                    : 'غير معتمد بعد';
                            }),
                        Toggle::make('teacher_may_edit_profile')
                            ->label('السماح للمعلّم بتعديل الملف الشخصي')
                            ->default(true),
                    ]),
            ]);
    }

    /**
     * جدول عرض الطلاب (List page).
     *
     * Arabic: يعرض بيانات التسجيل الحالي، ويقدّم فلاتر حسب المنطقة/المركز/الحلقة/حالة التسجيل،
     * بالإضافة إلى إجراءات تشغيلية على الطالب (نقل/إيقاف/السماح للمعلّم بالتعديل).
     * EN: Students listing table with multiple filters and operational actions.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean(),
                Tables\Columns\TextColumn::make('currentEnrollment.status')
                    ->label('حالة التسجيل')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        Enrollment::STATUS_ACTIVE => 'نشط',
                        Enrollment::STATUS_PAUSED => 'موقوف',
                        Enrollment::STATUS_GRADUATED => 'متخرج',
                        Enrollment::STATUS_DROPPED => 'منسحب',
                        default => $state ?? '-',
                    }),
                Tables\Columns\TextColumn::make('currentEnrollment.halaqah.name')
                    ->label('الحلقة الحالية')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('currentEnrollment.halaqah.center.name')
                    ->label('المركز')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('currentEnrollment.enrolled_at')
                    ->label('تاريخ التسجيل')
                    ->date('Y/m/d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('مفعّل'),
                Tables\Filters\SelectFilter::make('region_id')
                    ->label('المنطقة')
                    ->options(Region::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $regionId) {
                            $q->whereHas('enrollments.halaqah.center', fn (Builder $c) => $c->where('region_id', $regionId));
                        });
                    }),
                Tables\Filters\SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(\App\Models\Center::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $centerId) {
                            $q->whereHas('enrollments.halaqah', fn (Builder $h) => $h->where('center_id', $centerId));
                        });
                    }),
                Tables\Filters\SelectFilter::make('halaqah_id')
                    ->label('الحلقة')
                    ->options(Halaqah::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $halaqahId) {
                            $q->whereHas('enrollments', fn (Builder $e) => $e->where('halaqah_id', $halaqahId));
                        });
                    }),
                Tables\Filters\SelectFilter::make('enrollment_status')
                    ->label('حالة التسجيل')
                    ->options([
                        Enrollment::STATUS_ACTIVE => 'نشط',
                        Enrollment::STATUS_PAUSED => 'موقوف',
                        Enrollment::STATUS_GRADUATED => 'متخرج',
                        Enrollment::STATUS_DROPPED => 'منسحب',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $status) {
                            $q->whereHas('currentEnrollment', fn (Builder $e) => $e->where('status', $status));
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('allow_teacher_profile_edit')
                    ->label('السماح للمعلّم بتعديل الملف')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->visible(function (Student $record) {
                        $u = auth()->user();
                        if (! $u?->can('update', $record)) {
                            return false;
                        }

                        return $record->profile_locked && ! $record->teacher_may_edit_profile;
                    })
                    ->requiresConfirmation()
                    ->modalDescription('سيتمكن المعلّم من تعديل بيانات الطالب وإعادة رفعها للمراجعة.')
                    ->action(fn (Student $record) => $record->update(['teacher_may_edit_profile' => true])),
                Action::make('transfer')
                    ->label('نقل طالب')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Student $record) => auth()->user()?->can('update', $record) ?? false)
                    ->form([
                        Select::make('halaqah_id')
                            ->label('الحلقة الجديدة')
                            ->required()
                            ->options(fn () => self::getAllowedHalaqahOptions()),
                        DatePicker::make('enrolled_at')
                            ->label('تاريخ النقل')
                            ->default(now())
                            ->required()
                            ->native(false),
                        TextInput::make('leave_reason')
                            ->label('سبب إنهاء السابق')
                            ->default('Transferred')
                            ->maxLength(255),
                    ])
                    ->action(function (Student $record, array $data) {
                        $user = auth()->user();
                        abort_unless($user, 403);

                        $enrollmentPolicy = app(\App\Policies\EnrollmentPolicy::class);
                        abort_unless($enrollmentPolicy->createForStudent($user, $record, (int) $data['halaqah_id']), 403);

                        $current = $record->enrollments()->active()->first();
                        if ($current) {
                            $current->update([
                                'status' => Enrollment::STATUS_PAUSED,
                                'left_at' => now()->toDateString(),
                                'leave_reason' => $data['leave_reason'] ?? 'Transferred',
                            ]);
                        }

                        Enrollment::create([
                            'student_id' => $record->id,
                            'halaqah_id' => (int) $data['halaqah_id'],
                            'enrolled_at' => $data['enrolled_at'],
                            'status' => Enrollment::STATUS_ACTIVE,
                        ]);
                    }),
                Action::make('stop')
                    ->label('إيقاف/إنهاء')
                    ->icon('heroicon-o-stop-circle')
                    ->visible(fn (Student $record) => auth()->user()?->can('update', $record) ?? false)
                    ->form([
                        Select::make('status')
                            ->label('الحالة')
                            ->required()
                            ->options([
                                Enrollment::STATUS_PAUSED => 'موقوف',
                                Enrollment::STATUS_GRADUATED => 'متخرج',
                                Enrollment::STATUS_DROPPED => 'منسحب',
                            ]),
                        DatePicker::make('left_at')
                            ->label('تاريخ الإنهاء')
                            ->default(now())
                            ->required()
                            ->native(false),
                        TextInput::make('leave_reason')
                            ->label('السبب')
                            ->maxLength(255),
                    ])
                    ->action(function (Student $record, array $data) {
                        $current = $record->enrollments()->active()->first();
                        if (! $current) {
                            return;
                        }
                        $current->update([
                            'status' => $data['status'],
                            'left_at' => $data['left_at'],
                            'leave_reason' => $data['leave_reason'] ?? null,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    /**
     * صفحات المورد (List/Create/Edit).
     * EN: Resource pages routes.
     *
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب المستخدم الحالي.
     *
     * Arabic: SuperAdmin غير مقيّد. غير ذلك يتم تفويض تقييد الاستعلام إلى `StudentPolicy`
     * لضمان توحيد منطق الصلاحيات عبر النظام.
     * EN: Scopes query via StudentPolicy for non-SuperAdmin users.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with(['currentEnrollment.halaqah.center.region']);

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        $policy = app(\App\Policies\StudentPolicy::class);
        return $policy->scopeQueryForUser($user, $query);
    }

    /**
     * خيارات الحلقات المسموحة لاستخدامها في نموذج "نقل طالب".
     *
     * Arabic: يقيّد الخيارات حسب مراكز المستخدم المُدارة إن لم يكن SuperAdmin.
     * EN: Returns allowed halaqah options for transfer action based on user scope.
     *
     * @return array<int, string>
     */
    private static function getAllowedHalaqahOptions(): array
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $halaqahQuery = Halaqah::query()->with('center.region');

        if (! $user->hasRole('SuperAdmin')) {
            $centerIds = $user->managedCenters()->pluck('id');
            $halaqahQuery->whereIn('center_id', $centerIds);
        }

        return $halaqahQuery->get()->mapWithKeys(function (Halaqah $h) {
            $center = $h->center;
            $regionName = $center?->region?->name ?? '';
            $centerName = $center?->name ?? '';
            return [$h->id => trim("{$regionName} / {$centerName} / {$h->name}", ' /')];
        })->all();
    }
}
