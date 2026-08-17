<?php

namespace App\Filament\Resources\TestResultResource\Pages;

use App\Filament\Resources\TestResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة نتائج الاختبارات في Filament.
 *
 * Arabic: تعرض جدول النتائج وإجراء إنشاء نتيجة جديدة.
 * EN: Test results list page (table + create action).
 */
class ListTestResults extends ListRecords
{
    protected static string $resource = TestResultResource::class;

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
