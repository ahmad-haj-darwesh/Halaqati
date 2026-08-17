<?php

namespace App\Filament\Resources\HalaqahResource\Pages;

use App\Filament\Resources\HalaqahResource;
use App\Models\TeacherProfile;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * صفحة تعديل حلقة في Filament.
 *
 * Arabic: تتعامل مع تعيين المعلّم عبر `TeacherProfile` بشكل يدوي لضمان اتساق الربط:
 * - تعبئة حقل `teacher_profile_id` من العلاقة الحالية\n+ * - قبل الحفظ: استخراج القيمة وإزالتها من بيانات الحلقة\n+ * - بعد الحفظ: فك الربط عن كل ملفات المعلّمين المرتبطة بهذه الحلقة ثم ربط الملف المختار (إن وجد)
 *
 * EN: Edit page that synchronizes teacher assignment via TeacherProfile.halaqah_id.
 */
class EditHalaqah extends EditRecord
{
    protected static string $resource = HalaqahResource::class;

    protected ?int $teacherProfileIdToAssign = null;

    /**
     * إجراءات أعلى الصفحة.
     * EN: Header actions.
     */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * تعديل بيانات النموذج قبل تعبئتها في الحقول.
     *
     * Arabic: يضيف `teacher_profile_id` من العلاقة لعرضها في النموذج.
     * EN: Adds teacher_profile_id from the relationship for form display.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['teacher_profile_id'] = $this->record->teacherProfile?->id;

        return $data;
    }

    /**
     * تعديل بيانات النموذج قبل حفظ سجل الحلقة.
     *
     * Arabic: يستخرج `teacher_profile_id` ويزيله لأن الربط يتم عبر `TeacherProfile`.
     * EN: Extracts teacher_profile_id before saving; assignment is applied after save.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $raw = $data['teacher_profile_id'] ?? null;
        $this->teacherProfileIdToAssign = $raw !== null && $raw !== '' ? (int) $raw : null;
        unset($data['teacher_profile_id']);

        return $data;
    }

    /**
     * إجراء لاحق بعد حفظ الحلقة.
     *
     * Arabic: يضمن عدم بقاء أكثر من معلّم مرتبط بنفس الحلقة (تفكيك ثم ربط).
     * EN: Ensures teacher assignment is unique per halaqah by clearing then assigning.
     */
    protected function afterSave(): void
    {
        $halaqah = $this->record->fresh();

        TeacherProfile::query()
            ->where('halaqah_id', $halaqah->id)
            ->update(['halaqah_id' => null]);

        if ($this->teacherProfileIdToAssign) {
            TeacherProfile::query()
                ->whereKey($this->teacherProfileIdToAssign)
                ->update(['halaqah_id' => $halaqah->id]);
        }
    }
}
