<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * سياسة صلاحيات الطلاب (StudentPolicy).
 *
 * Arabic: تضبط من يمكنه عرض/إنشاء/تعديل/حذف الطلاب، وتوفّر دالة `scopeQueryForUser`
 * لتقييد استعلامات Eloquent حسب نطاق المستخدم (المراكز المُدارة) لغير SuperAdmin.
 * EN: Authorization policy for students with a query-scope helper for permission-aware listing.
 */
class StudentPolicy
{
    /**
     * صلاحية عرض قائمة الطلاب.
     * EN: Whether the user can view any students.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'EducationalSupervisor', 'CenterSupervisor', 'Examiner']);
    }

    /**
     * صلاحية عرض طالب محدد.
     * EN: Whether the user can view a specific student.
     */
    public function view(User $user, Student $student): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole(['Admin', 'EducationalSupervisor', 'CenterSupervisor', 'Examiner'])) {
            return $this->studentInUserScope($user, $student);
        }

        return false;
    }

    /**
     * صلاحية إنشاء طالب.
     * EN: Whether the user can create students.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية تعديل بيانات الطالب.
     * EN: Whether the user can update a student.
     */
    public function update(User $user, Student $student): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $this->studentInUserScope($user, $student);
        }

        return false;
    }

    /**
     * صلاحية حذف طالب.
     * EN: Whether the user can delete a student.
     */
    public function delete(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }

    /**
     * تقييد الاستعلام حسب نطاق المستخدم.
     *
     * Arabic: SuperAdmin غير مقيّد. غير ذلك يُقيّد حسب مراكز المستخدم المُدارة عبر
     * تحقق وجود تسجيلات ضمن حلقات تلك المراكز.
     * EN: Scopes the students query to managed centers for non-SuperAdmin users.
     */
    public function scopeQueryForUser(User $user, Builder $query): Builder
    {
        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        $centerIds = $user->managedCenters()->pluck('id');

        return $query->whereHas('enrollments.halaqah', function (Builder $q) use ($centerIds) {
            $q->whereIn('center_id', $centerIds);
        });
    }

    /**
     * التحقق أن الطالب ضمن نطاق المستخدم (مراكزه المُدارة).
     * EN: Checks whether a student is within the user's managed centers.
     */
    private function studentInUserScope(User $user, Student $student): bool
    {
        $centerIds = $user->managedCenters()->pluck('id');

        return $student->enrollments()
            ->whereHas('halaqah', fn (Builder $q) => $q->whereIn('center_id', $centerIds))
            ->exists();
    }
}

