<?php

namespace App\Policies;

use App\Models\TestAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * سياسة صلاحيات تعيينات الاختبارات (TestAssignmentPolicy).
 *
 * Arabic: تتحكم في صلاحيات عرض/إنشاء/تعديل/حذف تعيينات الاختبارات، وتقيّد الوصول
 * حسب المراكز المُدارة عبر علاقة الحلقة.
 * EN: Authorization policy for test assignments scoped by managed centers via the assignment's halaqah.
 */
class TestAssignmentPolicy
{
    /**
     * صلاحية عرض قائمة التعيينات.
     * EN: Whether the user can view any assignments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'Examiner']);
    }

    /**
     * صلاحية عرض تعيين محدد.
     * EN: Whether the user can view a specific assignment.
     */
    public function view(User $user, TestAssignment $assignment): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->hasRole(['Admin', 'Examiner']) && $this->assignmentInUserScope($user, $assignment);
    }

    /**
     * صلاحية إنشاء تعيين.
     * EN: Whether the user can create assignments.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'Examiner']);
    }

    /**
     * صلاحية تعديل تعيين.
     * EN: Whether the user can update an assignment.
     */
    public function update(User $user, TestAssignment $assignment): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->hasRole(['Admin', 'Examiner']) && $this->assignmentInUserScope($user, $assignment);
    }

    /**
     * صلاحية حذف تعيين.
     * EN: Whether the user can delete an assignment.
     */
    public function delete(User $user, TestAssignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    /**
     * تقييد استعلام التعيينات حسب المستخدم الحالي.
     *
     * Arabic: SuperAdmin غير مقيّد. غير ذلك: يقتصر على التعيينات ضمن حلقات تابعة لمراكز المستخدم المُدارة.
     * EN: Scopes assignments to halaqahs within the user's managed centers.
     */
    public function scopeQueryForUser(User $user, Builder $query): Builder
    {
        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        $centerIds = $user->managedCenters()->pluck('id');

        return $query->whereHas('halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $centerIds));
    }

    /**
     * التحقق أن التعيين ضمن نطاق المستخدم.
     * EN: Checks whether the assignment belongs to a halaqah in the user's managed centers.
     */
    private function assignmentInUserScope(User $user, TestAssignment $assignment): bool
    {
        $centerIds = $user->managedCenters()->pluck('id');
        return $assignment->halaqah()->whereIn('center_id', $centerIds)->exists();
    }
}

