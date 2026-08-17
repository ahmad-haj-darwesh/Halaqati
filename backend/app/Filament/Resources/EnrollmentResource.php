<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Filament\Resources\EnrollmentResource\RelationManagers;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * مورد Filament لإدارة التسجيلات (Enrollments).
 *
 * Arabic: يمثّل تسجيل الطالب في حلقة مع حالة التسجيل وتواريخ الدخول/الإنهاء وسبب الإنهاء،
 * ويقيّد عرض السجلات حسب المراكز المُدارة لغير SuperAdmin. كما يقيّد اختيار الطالب ليكون
 * من الطلاب الذين ليس لديهم تسجيل نشط (مع السماح بالطالب الحالي عند تعديل السجل).
 * EN: Filament resource for enrollments with permission-aware scoping and safe student selection.
 */
class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'التسجيلات';
    protected static ?string $modelLabel = 'تسجيل';
    protected static ?string $pluralModelLabel = 'التسجيلات';

    /**
     * نموذج إنشاء/تعديل التسجيل.
     *
     * Arabic: يختار الطالب من قائمة منضبطة (بدون تسجيل نشط)، ويحدّد الحلقة والحالة وتواريخ التسجيل/الإنهاء.
     * EN: Enrollment create/edit form schema.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('student_id')
                    ->label('الطالب')
                    ->required()
                    ->options(fn (?Enrollment $record): array => self::studentSelectOptions($record))
                    ->searchable(),
                Select::make('halaqah_id')
                    ->label('الحلقة')
                    ->required()
                    ->options(Halaqah::pluck('name', 'id'))
                    ->searchable(),
                DatePicker::make('enrolled_at')
                    ->label('تاريخ التسجيل')
                    ->default(now())
                    ->required()
                    ->native(false),
                Select::make('status')
                    ->label('الحالة')
                    ->required()
                    ->options([
                        Enrollment::STATUS_ACTIVE => 'نشط',
                        Enrollment::STATUS_PAUSED => 'موقوف',
                        Enrollment::STATUS_GRADUATED => 'متخرج',
                        Enrollment::STATUS_DROPPED => 'منسحب',
                    ])
                    ->default(Enrollment::STATUS_ACTIVE),
                DatePicker::make('left_at')
                    ->label('تاريخ الإنهاء')
                    ->native(false),
                TextInput::make('leave_reason')
                    ->label('السبب')
                    ->maxLength(255),
            ]);
    }

    /**
     * جدول عرض التسجيلات.
     *
     * Arabic: يعرض الطالب/الحلقة/المركز/الحالة وتاريخ التسجيل مع فلتر حسب الحالة.
     * EN: Enrollments listing table with status filter.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')->label('الطالب')->searchable(),
                Tables\Columns\TextColumn::make('halaqah.name')->label('الحلقة')->sortable(),
                Tables\Columns\TextColumn::make('halaqah.center.name')->label('المركز')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge(),
                Tables\Columns\TextColumn::make('enrolled_at')->label('التسجيل')->date('Y/m/d')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        Enrollment::STATUS_ACTIVE => 'نشط',
                        Enrollment::STATUS_PAUSED => 'موقوف',
                        Enrollment::STATUS_GRADUATED => 'متخرج',
                        Enrollment::STATUS_DROPPED => 'منسحب',
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
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب المستخدم الحالي.
     *
     * Arabic: SuperAdmin يرى كل التسجيلات. غير ذلك: يرى فقط تسجيلات الحلقات ضمن المراكز المُدارة.
     * EN: Scopes enrollments to managed centers for non-SuperAdmin users.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with(['student', 'halaqah.center.region']);

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        $centerIds = $user->managedCenters()->pluck('id');

        return $query->whereHas('halaqah', fn (Builder $q) => $q->whereIn('center_id', $centerIds));
    }

    /**
     * طلاب بلا تسجيل نشط في أي حلقة (أو الطالب الحالي عند تعديل السجل).
     *
     * Arabic: الغرض منع وجود أكثر من تسجيل نشط لطالب واحد من خلال واجهة الإدارة.
     * EN: Prevents selecting students who already have an active enrollment (except the current record's student).
     *
     * @param  Enrollment|null  $record
     * @return array<int, string>
     */
    private static function studentSelectOptions(?Enrollment $record): array
    {
        return Student::query()
            ->where(function (Builder $q) use ($record) {
                $q->whereDoesntHave(
                    'enrollments',
                    fn (Builder $e) => $e->where('status', Enrollment::STATUS_ACTIVE)
                );
                if ($record?->student_id) {
                    $q->orWhere('id', $record->student_id);
                }
            })
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all();
    }
}
