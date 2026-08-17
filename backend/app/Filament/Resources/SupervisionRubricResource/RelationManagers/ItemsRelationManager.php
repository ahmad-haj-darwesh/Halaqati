<?php

namespace App\Filament\Resources\SupervisionRubricResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * مدير علاقة محاور التقييم (Rubric Items) داخل Filament.
 *
 * Arabic: يمكّن إدارة عناصر/محاور القالب (key/label/max_score/sort_order/is_active) وتحديد ترتيب العرض.
 * EN: Manages rubric items for a supervision rubric.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'محاور التقييم';

    /**
     * نموذج إنشاء/تعديل محور.
     * EN: Rubric item form schema.
     */
    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('key')->label('Key')->required()->maxLength(255),
            TextInput::make('label')->label('المحور')->required()->maxLength(255),
            TextInput::make('max_score')->label('الحد الأعلى')->numeric()->default(5)->required(),
            TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
            Toggle::make('is_active')->label('مفعّل')->default(true),
        ])->columns(2);
    }

    /**
     * جدول عرض المحاور.
     *
     * Arabic: يعرض الترتيب/المحور/المفتاح/الحد الأعلى/التفعيل، مع فرز افتراضي حسب الترتيب.
     * EN: Rubric items table configuration.
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('ترتيب')->sortable(),
                Tables\Columns\TextColumn::make('label')->label('المحور')->searchable(),
                Tables\Columns\TextColumn::make('key')->label('Key')->toggleable(),
                Tables\Columns\TextColumn::make('max_score')->label('الحد الأعلى'),
                Tables\Columns\IconColumn::make('is_active')->label('مفعّل')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}

