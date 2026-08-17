<?php

namespace App\Filament\Resources\HalaqahResource\Pages;

use App\Filament\Resources\HalaqahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة الحلقات في Filament.
 *
 * Arabic: تعرض جدول الحلقات وإجراءات الهيدر مثل إنشاء حلقة جديدة.
 * EN: Halaqahs list page (table + header actions).
 */
class ListHalaqahs extends ListRecords
{
    protected static string $resource = HalaqahResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
