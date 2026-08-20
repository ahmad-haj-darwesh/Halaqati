<?php

namespace App\Services;

use App\Models\StudentProfileSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * خدمة إدارة قرارات مراجعة ملف الطالب.
 *
 * Arabic: تطبّق قرار المشرف/المراجع على طلب "إرسال الملف للمراجعة" عبر تحديث
 * الطالب (Student) وتحديث حالة الطلب (Submission) بشكل متسق داخل معاملة.
 * EN: Applies reviewer decisions (approve/reject) to a student profile submission.
 */
class StudentProfileSubmissionService
{
    /**
     * اعتماد الطلب وتثبيت بياناته على سجل الطالب.
     *
     * Arabic: ينقل الحقول من `StudentProfileSubmission` إلى الطالب ويقفل الملف
     * لمنع التعديل إلى أن يتم فتحه من الإدارة عند الحاجة.
     * EN: Approves the submission, persists its fields to the student, and locks the profile.
     *
     * @param  StudentProfileSubmission  $submission  طلب المراجعة المراد اعتماده
     * @param  User  $reviewer  المستخدم الذي قام بالمراجعة/الاعتماد
     */
    public function approve(StudentProfileSubmission $submission, User $reviewer): void
    {
        DB::transaction(function () use ($submission, $reviewer) {
            $student = $submission->student()->lockForUpdate()->first();

            // الصورة المعتمدة سابقاً لم تعد مستخدمة بعد قبول الصورة الجديدة.
            $oldPhoto = $student->photo_path;
            if ($oldPhoto && $oldPhoto !== $submission->photo_path) {
                $this->deletePhoto($oldPhoto);
            }

            $student->update([
                'full_name'                => $submission->full_name,
                'gender'                   => $submission->gender,
                'birth_date'               => $submission->birth_date,
                'guardian_name'            => $submission->guardian_name,
                'guardian_phone'           => $submission->guardian_phone,
                'national_id'              => $submission->national_id,
                'notes'                    => $submission->notes,
                'photo_path'               => $submission->photo_path,
                'profile_locked'           => true,
                'teacher_may_edit_profile' => false,
            ]);

            $submission->update([
                'status'                => StudentProfileSubmission::STATUS_APPROVED,
                'reviewed_by_user_id'   => $reviewer->id,
                'reviewed_at'           => now(),
                'reviewer_note'         => null,
            ]);
        });
    }

    /**
     * رفض الطلب مع حفظ ملاحظة اختيارية من المراجع.
     *
     * Arabic: لا يغيّر بيانات الطالب؛ فقط يحدّث حالة الطلب ومعلومات المراجعة.
     * EN: Rejects the submission and stores an optional reviewer note.
     *
     * @param  StudentProfileSubmission  $submission  طلب المراجعة المراد رفضه
     * @param  User  $reviewer  المستخدم الذي قام بالمراجعة/الرفض
     * @param  string|null  $note  ملاحظة المراجع (اختيارية)
     */
    public function reject(StudentProfileSubmission $submission, User $reviewer, ?string $note): void
    {
        DB::transaction(function () use ($submission, $reviewer, $note) {
            $student = $submission->student()->lockForUpdate()->first();

            // بيانات الطالب لم تُمس أصلاً (التعديلات تبقى في الطلب حتى الاعتماد)،
            // فيكفي إهمال المقترح وحذف الصورة المرفوعة معه لأنها صارت يتيمة.
            if ($submission->photo_path
                && $student
                && $submission->photo_path !== $student->photo_path) {
                $this->deletePhoto($submission->photo_path);
            }

            $submission->update([
                'status'              => StudentProfileSubmission::STATUS_REJECTED,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at'         => now(),
                'reviewer_note'       => $note,
            ]);
        });
    }

    /**
     * حذف صورة من قرص public إن وُجدت.
     * EN: Deletes a stored photo if present.
     */
    private function deletePhoto(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
