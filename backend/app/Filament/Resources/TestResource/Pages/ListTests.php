<?php

namespace App\Filament\Resources\TestResource\Pages;

use App\Filament\Resources\TestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة الاختبارات في Filament.
 *
 * Arabic: تعرض جدول الاختبارات وإجراء إنشاء اختبار جديد.
 * EN: Tests list page (table + create action).
 */
class ListTests extends ListRecords
{
    protected static string $resource = TestResource::class;

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
