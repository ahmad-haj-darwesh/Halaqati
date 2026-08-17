<?php

namespace App\Filament\Resources\TestResource\Pages;

use App\Filament\Resources\TestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل اختبار في Filament.
 *
 * Arabic: تتيح حذف السجل عبر إجراء الهيدر.
 * EN: Test edit page (with delete action).
 */
class EditTest extends EditRecord
{
    protected static string $resource = TestResource::class;

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
