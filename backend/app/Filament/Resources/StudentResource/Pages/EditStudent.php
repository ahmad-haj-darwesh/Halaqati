<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل طالب في Filament.
 *
 * Arabic: توفر إجراءات الهيدر مثل حذف السجل.
 * EN: Student edit page (with delete action).
 */
class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

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
