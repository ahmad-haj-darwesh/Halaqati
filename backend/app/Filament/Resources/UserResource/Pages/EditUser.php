<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل مستخدم في Filament.
 *
 * Arabic: تتيح حذف المستخدم عبر إجراء الهيدر.
 * EN: User edit page (with delete action).
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
