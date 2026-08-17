<?php

namespace App\Filament\Resources\TeacherProfileResource\Pages;

use App\Filament\Resources\TeacherProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة ملفات المعلّمين في Filament.
 *
 * Arabic: تعرض جدول المعلّمين وإجراءات الهيدر مثل إنشاء ملف معلّم جديد.
 * EN: Teacher profiles list page (table + header actions).
 */
class ListTeacherProfiles extends ListRecords
{
    protected static string $resource = TeacherProfileResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
