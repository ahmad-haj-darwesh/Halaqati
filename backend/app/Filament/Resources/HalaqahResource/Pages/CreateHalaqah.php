<?php

namespace App\Filament\Resources\HalaqahResource\Pages;

use App\Filament\Resources\HalaqahResource;
use App\Models\TeacherProfile;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء حلقة في Filament.
 *
 * Arabic: تتعامل مع حقل `teacher_profile_id` كقيمة "عرض" في النموذج، ثم بعد إنشاء
 * الحلقة تقوم بربط `TeacherProfile` المختار بالحَلَقة عبر تحديث `halaqah_id`.
 * EN: Halaqah creation page that assigns an optional teacher profile after create.
 */
class CreateHalaqah extends CreateRecord
{
    protected static string $resource = HalaqahResource::class;

    protected ?int $teacherProfileIdToAssign = null;

    /**
     * تعديل بيانات النموذج قبل إنشاء السجل.
     *
     * Arabic: يفصل `teacher_profile_id` عن بيانات الحلقة (لأنه يُدار في `TeacherProfile`).
     * EN: Extracts teacher_profile_id (managed on TeacherProfile) before creating the halaqah record.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $raw = $data['teacher_profile_id'] ?? null;
        $this->teacherProfileIdToAssign = $raw !== null && $raw !== '' ? (int) $raw : null;
        unset($data['teacher_profile_id']);

        return $data;
    }

    /**
     * إجراء لاحق بعد إنشاء الحلقة.
     *
     * Arabic: يربط المعلّم المختار بهذه الحلقة (إن وُجد اختيار).
     * EN: Assigns the selected teacher profile to the newly created halaqah.
     */
    protected function afterCreate(): void
    {
        if ($this->teacherProfileIdToAssign) {
            TeacherProfile::query()
                ->whereKey($this->teacherProfileIdToAssign)
                ->update(['halaqah_id' => $this->record->id]);
        }
    }
}
