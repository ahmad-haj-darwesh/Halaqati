<?php

namespace App\Filament\Resources\SupervisionRubricResource\Pages;

use App\Filament\Resources\SupervisionRubricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة قوالب الزيارات في Filament.
 *
 * Arabic: تعرض جدول القوالب وإجراء إنشاء قالب جديد.
 * EN: Supervision rubrics list page (table + create action).
 */
class ListSupervisionRubrics extends ListRecords
{
    protected static string $resource = SupervisionRubricResource::class;

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
