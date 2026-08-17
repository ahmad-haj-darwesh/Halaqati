<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة المستخدمين في Filament.
 *
 * Arabic: تعرض جدول المستخدمين وإجراءات الهيدر مثل إنشاء مستخدم جديد.
 * EN: Users list page (table + header actions).
 */
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
