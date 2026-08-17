<?php

namespace App\Filament\Resources\SupervisoryVisitResource\RelationManagers;

use App\Models\SupervisionRubricItem;
use App\Models\SupervisoryVisit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * RelationManager لدرجات محاور الزيارة الإشرافية.
 *
 * Arabic: يدير عناصر `scores` المرتبطة بالزيارة، ويعيد حساب الدرجة الإجمالية
 * (`overall_score`) بعد أي عملية إنشاء/تعديل/حذف.
 * EN: Manages visit score items and recomputes the overall score after changes.
 */
class ScoresRelationManager extends RelationManager
{
    protected static string $relationship = 'scores';

    protected static ?string $title = 'درجات المحاور';

    /**
     * نموذج إدخال درجة محور (Rubric item) للزيارة.
     *
     * Arabic: يقيّد الخيارات على عناصر القالب (Rubric) المرتبط بالزيارة الحالية.
     * EN: Limits rubric item options to the visit's rubric.
     */
    public function form(Form $form): Form
    {
        /** @var SupervisoryVisit $visit */
        $visit = $this->getOwnerRecord();

        return $form->schema([
            Select::make('supervision_rubric_item_id')
                ->label('المحور')
                ->required()
                ->options(function () use ($visit) {
                    return SupervisionRubricItem::where('supervision_rubric_id', $visit->supervision_rubric_id)
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->pluck('label', 'id');
                })
                ->searchable(),
            TextInput::make('score')->label('الدرجة')->numeric()->nullable(),
            Textarea::make('note')->label('ملاحظة')->rows(2),
        ]);
    }

    /**
     * جدول درجات المحاور داخل صفحة الزيارة.
     *
     * Arabic: يعيد حساب الدرجة الإجمالية بعد أي تغيير لضمان اتساق العرض والتقارير.
     * EN: Recomputes overall score after create/edit/delete.
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item.label')->label('المحور')->searchable(),
                Tables\Columns\TextColumn::make('score')->label('الدرجة'),
                Tables\Columns\TextColumn::make('note')->label('ملاحظة')->limit(30),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function () {
                        /** @var SupervisoryVisit $visit */
                        $visit = $this->getOwnerRecord();
                        $visit->recomputeOverallScore();
                        $visit->save();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        /** @var SupervisoryVisit $visit */
                        $visit = $this->getOwnerRecord();
                        $visit->recomputeOverallScore();
                        $visit->save();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        /** @var SupervisoryVisit $visit */
                        $visit = $this->getOwnerRecord();
                        $visit->recomputeOverallScore();
                        $visit->save();
                    }),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('item'));
    }
}

