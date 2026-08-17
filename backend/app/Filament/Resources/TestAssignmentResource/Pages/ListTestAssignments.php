<?php

namespace App\Filament\Resources\TestAssignmentResource\Pages;

use App\Filament\Resources\TestAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة تعيينات الاختبارات في Filament.
 *
 * Arabic: تعرض جدول التعيينات وإجراء إنشاء تعيين جديد.
 * EN: Test assignments list page (table + create action).
 */
class ListTestAssignments extends ListRecords
{
    protected static string $resource = TestAssignmentResource::class;

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
