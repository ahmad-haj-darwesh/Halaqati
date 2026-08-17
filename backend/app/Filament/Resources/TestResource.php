<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestResource\Pages;
use App\Filament\Resources\TestResource\RelationManagers;
use App\Models\Center;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Test;
use App\Services\SamplingAssignmentService;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
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
 * مورد Filament لإدارة الاختبارات (Tests).
 *
 * Arabic: يتيح إنشاء اختبار (عادي/عينات) مع تحديد نطاقه (منطقة/مركز/حلقة)،
 * ونشر الاختبار، وتوليد تعيينات عينات عبر `SamplingAssignmentService` مع تحقق صلاحيات.
 * كما يربط Relation Manager لعرض/إدارة تعيينات الاختبار.
 * EN: Filament resource for tests, supporting sampling settings, publishing, and sample assignment generation.
 */
class TestResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'الاختبارات';
    protected static ?string $modelLabel = 'اختبار';
    protected static ?string $pluralModelLabel = 'الاختبارات';
    protected static ?int $navigationSort = 7;

    /**
     * نموذج إنشاء/تعديل الاختبار.
     *
     * Arabic:
     * - يدعم نوعين: عادي/عينات.\n+     * - عند اختيار نوع "عينات" تظهر إعدادات اختيار العينة (strategy/count/percent/seed/active_only).\n+     * - يسمح بتحديد نطاق اختياري: منطقة/مركز/حلقة.
     *
     * EN: Test form schema including conditional sampling settings and optional scope.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('type')
                    ->label('نوع الاختبار')
                    ->required()
                    ->options([
                        Test::TYPE_REGULAR => 'عادي',
                        Test::TYPE_SAMPLING => 'عينات',
                    ])
                    ->default(Test::TYPE_REGULAR),
                TextInput::make('title')->label('العنوان')->required()->maxLength(255),
                Textarea::make('description')->label('الوصف')->rows(3),
                DateTimePicker::make('scheduled_at')->label('موعد الاختبار'),

                Fieldset::make('النطاق')
                    ->schema([
                        Select::make('scope_region_id')
                            ->label('المنطقة')
                            ->options(Region::pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Select::make('scope_center_id')
                            ->label('المركز')
                            ->options(Center::pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Select::make('scope_halaqah_id')
                            ->label('الحلقة')
                            ->options(Halaqah::pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                    ])->columns(3),

                Fieldset::make('إعدادات العينات (Sampling)')
                    ->visible(fn (Forms\Get $get) => $get('type') === Test::TYPE_SAMPLING)
                    ->schema([
                        Select::make('sampling_strategy')
                            ->label('طريقة الاختيار')
                            ->options([
                                'random' => 'عشوائي',
                                'stratified' => 'موزّع على الحلقات',
                            ])
                            ->default('random')
                            ->nullable(),
                        TextInput::make('sampling_count')
                            ->label('عدد الطلاب')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                        TextInput::make('sampling_percent')
                            ->label('نسبة مئوية %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable(),
                        TextInput::make('sampling_seed')
                            ->label('Seed (اختياري لتكرار نفس العينة)')
                            ->nullable(),
                        Toggle::make('sampling_active_only')
                            ->label('طلاب نشطون فقط')
                            ->default(true),
                    ])->columns(3),

                Toggle::make('is_published')->label('منشور')->default(false),
            ]);
    }

    /**
     * جدول عرض الاختبارات.
     *
     * Arabic: يعرض العنوان/النوع/حالة النشر/الموعد وعدد التعيينات، مع إجراءات:
     * - تعديل\n+     * - نشر الاختبار\n+     * - توليد تعيينات العينات (للاختبارات من نوع Sampling) مع نموذج إدخال بسيط
     *
     * EN: Tests listing table with publish and generate-sample actions.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge(),
                Tables\Columns\IconColumn::make('is_published')->label('منشور')->boolean(),
                Tables\Columns\TextColumn::make('scheduled_at')->label('الموعد')->dateTime('Y/m/d H:i')->sortable(),
                Tables\Columns\TextColumn::make('assignments_count')->label('التعيينات')->counts('assignments'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([Test::TYPE_REGULAR => 'عادي', Test::TYPE_SAMPLING => 'عينات']),
                Tables\Filters\TernaryFilter::make('is_published')->label('منشور'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('publish')
                    ->label('نشر الاختبار')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn (Test $record) => ! $record->is_published)
                    ->action(fn (Test $record) => $record->update(['is_published' => true])),
                Action::make('generateSample')
                    ->label('Generate Sample Assignments')
                    ->icon('heroicon-o-sparkles')
                    ->visible(fn (Test $record) => $record->type === Test::TYPE_SAMPLING)
                    ->form([
                        Select::make('strategy')
                            ->label('الطريقة')
                            ->options(['random' => 'عشوائي', 'stratified' => 'موزّع'])
                            ->default(fn (Test $record) => $record->sampling_strategy ?? 'random')
                            ->required(),
                        TextInput::make('count')->label('العدد')->numeric()->nullable(),
                        TextInput::make('percent')->label('النسبة %')->numeric()->nullable(),
                        TextInput::make('seed')->label('Seed')->nullable(),
                        Toggle::make('active_only')->label('نشط فقط')->default(true),
                    ])
                    ->action(function (Test $record, array $data) {
                        $user = auth()->user();
                        abort_unless($user, 403);
                        abort_unless($user->can('update', $record), 403);

                        $service = app(SamplingAssignmentService::class);
                        $service->generate($record, $user, $data);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * العلاقات ضمن صفحة الاختبار (Relation Managers).
     * EN: Resource relations.
     *
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\AssignmentsRelationManager::class,
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
            'index' => Pages\ListTests::route('/'),
            'create' => Pages\CreateTest::route('/create'),
            'edit' => Pages\EditTest::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب المستخدم الحالي عبر `TestPolicy`.
     *
     * Arabic: يفوّض منطق النطاق والصلاحيات إلى Policy لضمان توحيد القيود عبر النظام.
     * EN: Scopes query using TestPolicy for consistent authorization rules.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        $policy = app(\App\Policies\TestPolicy::class);
        return $policy->scopeQueryForUser($user, $query);
    }
}
