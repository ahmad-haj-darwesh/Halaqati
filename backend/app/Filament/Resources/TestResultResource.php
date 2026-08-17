<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestResultResource\Pages;
use App\Filament\Resources\TestResultResource\RelationManagers;
use App\Models\TestResult;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * مورد Filament لإدارة نتائج الاختبارات (Test Results).
 *
 * Arabic: يمكّن إدارة نتيجة التعيين (TestAssignment) من حيث المختبر والمجموع والمستوى وتاريخ الاختبار،
 * ويعرض جدولاً يُظهر سياق النتيجة (الاختبار/الطالب/المركز/الحلقة) مع فلتر حسب المستوى،
 * كما يقيّد الاستعلام حسب الصلاحيات عبر `TestResultPolicy`.
 * EN: Filament resource for test results with contextual listing and policy-based scoping.
 */
class TestResultResource extends Resource
{
    protected static ?string $model = TestResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'نتائج الاختبارات';
    protected static ?string $modelLabel = 'نتيجة';
    protected static ?string $pluralModelLabel = 'نتائج الاختبارات';

    /**
     * نموذج إنشاء/تعديل النتيجة.
     *
     * Arabic: يربط النتيجة بتعيين ومختبر، ويتيح إدخال المجموع/المستوى/التاريخ والملاحظات.
     * EN: TestResult form schema.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('test_assignment_id')
                    ->label('التعيين')
                    ->relationship('assignment', 'id')
                    ->searchable()
                    ->required(),
                Select::make('examiner_user_id')
                    ->label('المختبر')
                    ->relationship('examiner', 'name')
                    ->required(),
                TextInput::make('total_score')->label('المجموع')->numeric()->nullable(),
                Select::make('level')
                    ->label('المستوى')
                    ->options([
                        TestResult::LEVEL_EXCELLENT => 'ممتاز',
                        TestResult::LEVEL_GOOD => 'جيد',
                        TestResult::LEVEL_ACCEPTABLE => 'مقبول',
                        TestResult::LEVEL_WEAK => 'ضعيف',
                    ])
                    ->nullable(),
                DateTimePicker::make('tested_at')->label('تاريخ الاختبار')->default(now()),
                Textarea::make('notes')->label('ملاحظات')->rows(3),
            ]);
    }

    /**
     * جدول عرض النتائج.
     *
     * Arabic: يعرض الاختبار/الطالب/المركز/الحلقة والمستوى والمجموع وتاريخ الاختبار.
     * EN: Results listing table with level filter.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('assignment.test.title')->label('الاختبار')->searchable(),
                Tables\Columns\TextColumn::make('assignment.student.full_name')->label('الطالب')->searchable(),
                Tables\Columns\TextColumn::make('assignment.halaqah.center.name')->label('المركز')->sortable(),
                Tables\Columns\TextColumn::make('assignment.halaqah.name')->label('الحلقة')->sortable(),
                Tables\Columns\TextColumn::make('level')->label('المستوى')->badge(),
                Tables\Columns\TextColumn::make('total_score')->label('المجموع')->sortable(),
                Tables\Columns\TextColumn::make('tested_at')->label('التاريخ')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('المستوى')
                    ->options([
                        TestResult::LEVEL_EXCELLENT => 'excellent',
                        TestResult::LEVEL_GOOD => 'good',
                        TestResult::LEVEL_ACCEPTABLE => 'acceptable',
                        TestResult::LEVEL_WEAK => 'weak',
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
            'index' => Pages\ListTestResults::route('/'),
            'create' => Pages\CreateTestResult::route('/create'),
            'edit' => Pages\EditTestResult::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب المستخدم الحالي عبر `TestResultPolicy`.
     *
     * Arabic: يفوّض منطق النطاق إلى Policy لضمان تطبيق القيود عبر جميع الواجهات.
     * EN: Scopes query using TestResultPolicy for consistent authorization rules.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with(['assignment.test', 'assignment.student', 'assignment.halaqah.center', 'examiner']);

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        $policy = app(\App\Policies\TestResultPolicy::class);
        return $policy->scopeQueryForUser($user, $query);
    }
}
