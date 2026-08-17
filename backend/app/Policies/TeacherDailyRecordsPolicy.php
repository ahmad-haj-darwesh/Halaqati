<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;

/**
 * سياسة صلاحيات المعلّم للسجلات اليومية (TeacherDailyRecordsPolicy).
 *
 * Arabic: تتحقق من أن المعلّم يمكنه الوصول/الكتابة على بيانات طالب ضمن حلقته الحالية
 * (تسجيل نشط)، وتضبط قواعد تعديل/رفع ملف الطالب للمراجعة حسب السماح ووجود طلب معلّق.
 * EN: Helper policy for teacher access to daily records and profile edit/submit rules.
 */
class TeacherDailyRecordsPolicy
{
    /**
     * التحقق أن المعلّم يمكنه الوصول إلى الطالب (ضمن حلقته وبحالة تسجيل نشط).
     *
     * Arabic: يمكن استخدام `$date` لاحقاً لتوسيع المنطق إلى "النطاق بتاريخ معيّن"، لكنه غير مستخدم حالياً.
     * EN: Checks that the teacher can access the student (active enrollment in teacher's halaqah). $date reserved for future use.
     */
    public function teacherCanAccessStudent(User $user, Student $student, ?string $date = null): bool
    {
        if (! $user->hasRole('Teacher')) {
            return false;
        }

        $halaqahId = $user->teacherProfile?->halaqah_id;
        if (! $halaqahId) {
            return false;
        }

        // Student must be currently active in teacher halaqah
        return $student->enrollments()
            ->where('halaqah_id', $halaqahId)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * التحقق أن المعلّم يمكنه الكتابة/التسجيل لطالب ضمن حلقة معيّنة.
     *
     * Arabic: يمنع الكتابة إذا كانت الحلقة المطلوبة لا تطابق حلقة المعلّم الحالية.
     * EN: Ensures writes happen only for the teacher's own halaqah.
     */
    public function teacherCanWriteForStudent(User $user, Student $student, int $halaqahId): bool
    {
        if (! $user->hasRole('Teacher')) {
            return false;
        }

        $teacherHalaqahId = $user->teacherProfile?->halaqah_id;
        if (! $teacherHalaqahId) {
            return false;
        }

        if ($halaqahId !== $teacherHalaqahId) {
            return false;
        }

        return $this->teacherCanAccessStudent($user, $student);
    }

    /**
     * تعديل بيانات الملف الشخصي على الطالب (قبل رفع الطلب للمشرف أو بعد السماح بالتعديل).
     *
     * Arabic: يشترط:
     * - الطالب ضمن حلقة المعلّم وتسجيله نشط\n+     * - `teacher_may_edit_profile` مفعّل\n+     * - لا يوجد طلب مراجعة معلّق (pending)
     *
     * EN: Requires active access, teacher_may_edit_profile, and no pending submission.
     */
    public function teacherCanEditStudentProfile(User $user, Student $student): bool
    {
        if (! $this->teacherCanAccessStudent($user, $student)) {
            return false;
        }

        if (! $student->teacher_may_edit_profile) {
            return false;
        }

        return ! $student->pendingProfileSubmission()->exists();
    }

    /**
     * رفع الملف للمراجعة عند عدم وجود طلب معلّق.
     *
     * Arabic: نفس شروط التعديل تقريباً، مع التأكد من عدم وجود طلب معلّق قبل السماح بالإرسال.
     * EN: Same conditions as edit; allows submitting only when no pending submission exists.
     */
    public function teacherCanSubmitStudentProfile(User $user, Student $student): bool
    {
        if (! $this->teacherCanAccessStudent($user, $student)) {
            return false;
        }

        if (! $student->teacher_may_edit_profile) {
            return false;
        }

        return ! $student->pendingProfileSubmission()->exists();
    }
}

