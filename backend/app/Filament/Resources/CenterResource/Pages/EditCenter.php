<?php

namespace App\Filament\Resources\CenterResource\Pages;

use App\Filament\Resources\CenterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل مركز في Filament.
 *
 * Arabic: توفر إجراءات الهيدر مثل حذف السجل.
 * EN: Center edit page (with delete action).
 */
class EditCenter extends EditRecord
{
    protected static string $resource = CenterResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
