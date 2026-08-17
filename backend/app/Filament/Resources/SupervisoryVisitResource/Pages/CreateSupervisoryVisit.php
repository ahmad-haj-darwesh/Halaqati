<?php

namespace App\Filament\Resources\SupervisoryVisitResource\Pages;

use App\Filament\Resources\SupervisoryVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء زيارة إشرافية في Filament.
 *
 * Arabic: تضيف `supervisor_user_id` تلقائياً للمستخدم الحالي عند الإنشاء.
 * EN: Sets supervisor_user_id to the current user before record creation.
 */
class CreateSupervisoryVisit extends CreateRecord
{
    protected static string $resource = SupervisoryVisitResource::class;

    /**
     * تعديل بيانات النموذج قبل إنشاء السجل.
     * EN: Mutates form data before creating the record.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['supervisor_user_id'] = auth()->id();
        return $data;
    }
}
