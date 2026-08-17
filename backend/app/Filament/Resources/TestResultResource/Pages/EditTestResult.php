<?php

namespace App\Filament\Resources\TestResultResource\Pages;

use App\Filament\Resources\TestResultResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل نتيجة اختبار في Filament.
 *
 * Arabic: تتيح حذف السجل عبر إجراء الهيدر.
 * EN: Test result edit page (with delete action).
 */
class EditTestResult extends EditRecord
{
    protected static string $resource = TestResultResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
