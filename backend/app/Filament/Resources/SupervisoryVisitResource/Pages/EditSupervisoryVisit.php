<?php

namespace App\Filament\Resources\SupervisoryVisitResource\Pages;

use App\Filament\Resources\SupervisoryVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل زيارة إشرافية في Filament.
 *
 * Arabic: تمنع تعديل زيارة "معتمدة/نهائية" للمستخدمين غير SuperAdmin.
 * EN: Edit page that blocks edits on finalized visits for non-SuperAdmin users.
 */
class EditSupervisoryVisit extends EditRecord
{
    protected static string $resource = SupervisoryVisitResource::class;

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

    /**
     * تعديل بيانات النموذج قبل الحفظ.
     *
     * Arabic: في حال كانت الزيارة معتمدة (`is_finalized`) يمنع التعديل إلا لـ SuperAdmin.
     * EN: Prevents saving changes for finalized visits unless SuperAdmin.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->is_finalized && ! auth()->user()?->hasRole('SuperAdmin')) {
            abort(403);
        }

        return $data;
    }
}
