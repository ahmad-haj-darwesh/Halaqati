<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة الطلاب في Filament.
 *
 * Arabic: تعرض جدول الطلاب مع إمكانية إنشاء طالب جديد.
 * EN: Students list page (table + create action).
 */
class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

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
