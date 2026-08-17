<?php

namespace App\Filament\Resources\SupervisoryVisitResource\Pages;

use App\Filament\Resources\SupervisoryVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة الزيارات الإشرافية في Filament.
 *
 * Arabic: تعرض جدول الزيارات وإجراء إنشاء زيارة جديدة.
 * EN: Supervisory visits list page (table + create action).
 */
class ListSupervisoryVisits extends ListRecords
{
    protected static string $resource = SupervisoryVisitResource::class;

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
