<?php

namespace App\Filament\Resources\TeacherProfileResource\Pages;

use App\Filament\Resources\TeacherProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل ملف معلّم في Filament.
 *
 * Arabic: تتيح حذف السجل عبر إجراء الهيدر.
 * EN: TeacherProfile edit page (with delete action).
 */
class EditTeacherProfile extends EditRecord
{
    protected static string $resource = TeacherProfileResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
