<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة المناطق في Filament.
 *
 * Arabic: تعرض جدول المناطق وإجراءات الهيدر مثل إنشاء منطقة جديدة.
 * EN: Regions list page (table + header actions).
 */
class ListRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
