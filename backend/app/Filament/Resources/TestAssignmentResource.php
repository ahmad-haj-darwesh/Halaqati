<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestAssignmentResource\Pages;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Policies\TestPolicy;
use App\Support\UserScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * مورد Filament لإدارة تعيينات الاختبارات (Test Assignments).
 *
 * Arabic: يتيح إنشاء تعيينات لاختبار معيّن لعدة طلاب داخل حلقة واحدة في عملية واحدة،
 * مع تقييد نطاق الاختبارات/الحلقات حسب صلاحيات المستخدم (عبر Policies و`UserScope`)،
 * ويعرض جدولاً للتعيينات مع حالة التعيين ووجود نتيجة.
 * EN: Filament resource for test assignments with multi-student creation and permission-aware scoping.
 */
class TestAssignmentResource extends Resource
{
    protected static ?string $model = TestAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'تعيينات الاختبارات';
    protected static ?string $modelLabel = 'تعيين';
    protected static ?string $pluralModelLabel = 'تعيينات الاختبارات';

    /**
     * نموذج إنشاء/تعديل التعيين.
     *
     * Arabic:
     * - في الإنشاء: اختيار الاختبار + الحلقة + مجموعة طلاب (`student_ids`) لإنشاء تعيين منفصل لكل طالب.\n+     * - في التعديل: يُعرض `student_id` كمرجع فقط (غير قابل للتعديل هنا).
     *
     * EN: TestAssignment form schema supports multi-student creation and read-only student on edit.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('test_id')
                    ->label('الاختبار')
                    ->required()
                    ->searchable()
                    ->disabledOn('edit')
                    ->options(function (): array {
                        $user = auth()->user();
                        if (! $user) {
                            return [];
                        }
                        $query = Test::query()->orderByDesc('scheduled_at')->orderByDesc('id');

                        return app(TestPolicy::class)
                            ->scopeQueryForUser($user, $query)
                            ->pluck('title', 'id')
                            ->all();
                    }),
                Select::make('halaqah_id')
                    ->label('الحلقة')
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('student_ids', []))
                    ->disabledOn('edit')
                    ->options(function (): array {
                        $user = auth()->user();
                        if (! $user) {
                            return [];
                        }
                        $q = Halaqah::query()->orderBy('name');
                        app(UserScope::class)->applyCentersToHalaqahsQuery($user, $q);

                        return $q->pluck('name', 'id')->all();
                    }),
                Select::make('student_ids')
                    ->label('الطلاب')
                    ->helperText('يمكنك اختيار أكثر من طالب؛ يُنشأ تعيين منفصل لكل طالب.')
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->preload()
                    ->visibleOn('create')
                    ->options(function (Get $get): array {
                        $halaqahId = $get('halaqah_id');
                        if (! $halaqahId) {
                            return [];
                        }

                        return Student::query()
                            ->whereHas('enrollments', function (Builder $q) use ($halaqahId) {
                                $q->where('halaqah_id', $halaqahId)
                                    ->where('status', Enrollment::STATUS_ACTIVE);
                            })
                            ->orderBy('full_name')
                            ->pluck('full_name', 'id')
                            ->all();
                    }),
                Select::make('student_id')
                    ->label('الطالب')
                    ->relationship('student', 'full_name')
                    ->disabled()
                    ->visibleOn('edit'),
                DateTimePicker::make('assigned_at')
                    ->label('تاريخ التعيين')
                    ->default(now())
                    ->disabledOn('edit'),
                Select::make('status')
                    ->label('الحالة')
                    ->options([
                        TestAssignment::STATUS_ASSIGNED => 'مُعيَّن',
                        TestAssignment::STATUS_COMPLETED => 'مكتمل',
                        TestAssignment::STATUS_ABSENT_EXCUSED => 'غياب مبرر',
                        TestAssignment::STATUS_ABSENT_UNEXCUSED => 'غياب غير مبرر',
                    ])
                    ->required()
                    ->default(TestAssignment::STATUS_ASSIGNED),
            ]);
    }

    /**
     * جدول عرض التعيينات.
     *
     * Arabic: يعرض الاختبار/الطالب/الحلقة/المركز والحالة ووجود نتيجة، مع فلتر حسب الحالة.
     * EN: Assignments listing table with status filter.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('test.title')->label('الاختبار')->searchable(),
                Tables\Columns\TextColumn::make('student.full_name')->label('الطالب')->searchable(),
                Tables\Columns\TextColumn::make('halaqah.name')->label('الحلقة')->sortable(),
                Tables\Columns\TextColumn::make('halaqah.center.name')->label('المركز')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge(),
                Tables\Columns\IconColumn::make('result.id')->label('تمت النتيجة')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        TestAssignment::STATUS_ASSIGNED => 'assigned',
                        TestAssignment::STATUS_COMPLETED => 'completed',
                        TestAssignment::STATUS_ABSENT_EXCUSED => 'absent_excused',
                        TestAssignment::STATUS_ABSENT_UNEXCUSED => 'absent_unexcused',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * العلاقات (Relation Managers) التابعة للمورد.
     * EN: Resource relations.
     *
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            //
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
            'index' => Pages\ListTestAssignments::route('/'),
            'create' => Pages\CreateTestAssignment::route('/create'),
            'edit' => Pages\EditTestAssignment::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب المستخدم الحالي عبر `TestAssignmentPolicy`.
     *
     * Arabic: يفوّض منطق النطاق إلى Policy لضمان تطبيق نفس القيود في كل الشاشات.
     * EN: Scopes query using TestAssignmentPolicy for consistent authorization rules.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with(['test', 'student', 'halaqah.center', 'result']);

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        $policy = app(\App\Policies\TestAssignmentPolicy::class);
        return $policy->scopeQueryForUser($user, $query);
    }
}
