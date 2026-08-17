<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل تسجيل في Filament.
 *
 * Arabic: تتيح حذف السجل عبر إجراء الهيدر.
 * EN: Enrollment edit page (with delete action).
 */
class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

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
