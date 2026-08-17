<?php

namespace App\Filament\Resources\SupervisionRubricResource\Pages;

use App\Filament\Resources\SupervisionRubricResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل قالب زيارة في Filament.
 *
 * Arabic: تتيح حذف السجل عبر إجراء الهيدر.
 * EN: Supervision rubric edit page (with delete action).
 */
class EditSupervisionRubric extends EditRecord
{
    protected static string $resource = SupervisionRubricResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
