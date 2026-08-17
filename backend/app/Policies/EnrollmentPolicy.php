<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * سياسة صلاحيات التسجيلات (EnrollmentPolicy).
 *
 * Arabic: تتحكم في صلاحيات عرض/إنشاء/تعديل/حذف التسجيلات، وتستخدم نطاق المراكز المُدارة
 * لغير SuperAdmin للتحقق من الوصول إلى تسجيل/حلقة معيّنة.
 * EN: Authorization policy for enrollments with managed-centers scoping.
 */
class EnrollmentPolicy
{
    /**
     * صلاحية عرض قائمة التسجيلات.
     * EN: Whether the user can view any enrollments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'EducationalSupervisor', 'CenterSupervisor', 'Examiner']);
    }

    /**
     * صلاحية عرض تسجيل محدد.
     * EN: Whether the user can view a specific enrollment.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole(['Admin', 'EducationalSupervisor', 'CenterSupervisor', 'Examiner'])) {
            return $this->enrollmentInUserScope($user, $enrollment);
        }

        return false;
    }

    /**
     * صلاحية إنشاء تسجيل.
     * EN: Whether the user can create enrollments.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية إنشاء تسجيل لطالب ضمن حلقة محددة.
     *
     * Arabic: Admin (ضمن نطاقه) أو SuperAdmin. تُستخدم عادةً قبل إنشاء Enrollment
     * من لوحة الإدارة أو عبر خدمات داخلية.
     * EN: Whether the user can create an enrollment for a student in a given halaqah.
     */
    public function createForStudent(User $user, Student $student, int $halaqahId): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if (! $user->hasRole('Admin')) {
            return false;
        }

        $centerIds = $user->managedCenters()->pluck('id');

        return \App\Models\Halaqah::query()
            ->whereKey($halaqahId)
            ->whereIn('center_id', $centerIds)
            ->exists();
    }

    /**
     * صلاحية تعديل تسجيل.
     * EN: Whether the user can update an enrollment.
     */
    public function update(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $this->enrollmentInUserScope($user, $enrollment);
        }

        return false;
    }

    /**
     * صلاحية حذف تسجيل.
     * EN: Whether the user can delete an enrollment.
     */
    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $this->update($user, $enrollment);
    }

    /**
     * التحقق أن التسجيل ضمن نطاق المستخدم (مراكزه المُدارة).
     * EN: Checks whether an enrollment belongs to a halaqah in the user's managed centers.
     */
    private function enrollmentInUserScope(User $user, Enrollment $enrollment): bool
    {
        $centerIds = $user->managedCenters()->pluck('id');

        return $enrollment->halaqah()
            ->whereIn('center_id', $centerIds)
            ->exists();
    }
}

