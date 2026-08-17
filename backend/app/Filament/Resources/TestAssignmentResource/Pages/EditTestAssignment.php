<?php

namespace App\Filament\Resources\TestAssignmentResource\Pages;

use App\Filament\Resources\TestAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل تعيين اختبار في Filament.
 *
 * Arabic: تتيح حذف السجل عبر إجراء الهيدر.
 * EN: Test assignment edit page (with delete action).
 */
class EditTestAssignment extends EditRecord
{
    protected static string $resource = TestAssignmentResource::class;

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
