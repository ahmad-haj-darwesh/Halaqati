<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupervisionRubricResource\Pages;
use App\Filament\Resources\SupervisionRubricResource\RelationManagers;
use App\Models\SupervisionRubric;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * مورد Filament لإدارة قوالب الزيارات/الإشراف (Supervision Rubrics).
 *
 * Arabic: القالب يحتوي محاور تقييم (items) تُستخدم في الزيارات الإشرافية لتسجيل الدرجات.
 * يوفّر إدارة القالب (الاسم/الوصف/التفعيل) وإدارة محاوره عبر Relation Manager،
 * ويضيف `created_by_user_id` تلقائياً عند الإنشاء، ويحمّل عدّاد المحاور في الاستعلام.
 * EN: Filament resource for supervision rubrics with items relation and automatic created_by tracking.
 */
class SupervisionRubricResource extends Resource
{
    protected static ?string $model = SupervisionRubric::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';
    protected static ?string $navigationLabel = 'قوالب الزيارات';
    protected static ?string $modelLabel = 'قالب';
    protected static ?string $pluralModelLabel = 'قوالب الزيارات';
    protected static ?int $navigationSort = 8;

    /**
     * نموذج إنشاء/تعديل القالب.
     * EN: Rubric form schema.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات القالب')
                    ->schema([
                        TextInput::make('name')->label('الاسم')->required()->maxLength(255),
                        Textarea::make('description')->label('الوصف')->rows(3),
                        Toggle::make('is_active')->label('مفعّل')->default(true),
                    ]),
            ]);
    }

    /**
     * جدول عرض القوالب.
     *
     * Arabic: يعرض الاسم/حالة التفعيل/عدد المحاور/تاريخ الإنشاء، مع فلتر للتفعيل.
     * EN: Rubrics listing table with active filter and items count.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('مفعّل')->boolean(),
                Tables\Columns\TextColumn::make('items_count')->label('عدد المحاور')->counts('items'),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->date('Y/m/d')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('مفعّل'),
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
     * العلاقات ضمن صفحة القالب.
     * EN: Resource relations.
     *
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
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
            'index' => Pages\ListSupervisionRubrics::route('/'),
            'create' => Pages\CreateSupervisionRubric::route('/create'),
            'edit' => Pages\EditSupervisionRubric::route('/{record}/edit'),
        ];
    }

    /**
     * تعديل بيانات النموذج قبل الإنشاء.
     *
     * Arabic: يضيف معرّف المستخدم المنشئ لأن الحقل مطلوب في قاعدة البيانات.
     * EN: Sets created_by_user_id automatically.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();
        return $data;
    }

    /**
     * تخصيص استعلام المورد.
     *
     * Arabic: يضيف `withCount('items')` لعرض عدد المحاور في الجدول.
     * EN: Adds items count for listing.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('items');
    }
}
