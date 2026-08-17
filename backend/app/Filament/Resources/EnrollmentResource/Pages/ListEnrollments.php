<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة التسجيلات في Filament.
 *
 * Arabic: تعرض جدول التسجيلات وإجراءات الهيدر مثل إضافة تسجيل جديد.
 * EN: Enrollments list page (table + create action).
 */
class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
