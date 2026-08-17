<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل منطقة في Filament.
 *
 * Arabic: توفر إجراءات الهيدر مثل حذف السجل.
 * EN: Region edit page (with delete action).
 */
class EditRegion extends EditRecord
{
    protected static string $resource = RegionResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
