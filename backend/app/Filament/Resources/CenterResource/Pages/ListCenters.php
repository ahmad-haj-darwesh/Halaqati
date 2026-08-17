<?php

namespace App\Filament\Resources\CenterResource\Pages;

use App\Filament\Resources\CenterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة المراكز في Filament.
 *
 * Arabic: تعرض جدول المراكز وإجراءات الهيدر (مثل إنشاء مركز جديد) وفق صلاحيات المستخدم.
 * EN: Centers list page (table + header actions).
 */
class ListCenters extends ListRecords
{
    protected static string $resource = CenterResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
