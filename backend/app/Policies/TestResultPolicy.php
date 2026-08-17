<?php

namespace App\Policies;

use App\Models\TestResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * سياسة صلاحيات نتائج الاختبارات (TestResultPolicy).
 *
 * Arabic: تتحكم في صلاحيات عرض/إنشاء/تعديل/حذف نتائج الاختبارات، وتقيّد الوصول
 * حسب المراكز المُدارة عبر تعيين الاختبار ثم الحلقة.
 * EN: Authorization policy for test results scoped by managed centers via assignment -> halaqah.
 */
class TestResultPolicy
{
    /**
     * صلاحية عرض قائمة النتائج.
     * EN: Whether the user can view any results.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'Examiner']);
    }

    /**
     * صلاحية عرض نتيجة محددة.
     * EN: Whether the user can view a specific result.
     */
    public function view(User $user, TestResult $result): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->hasRole(['Admin', 'Examiner']) && $this->resultInUserScope($user, $result);
    }

    /**
     * صلاحية إنشاء نتيجة.
     * EN: Whether the user can create results.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'Examiner']);
    }

    /**
     * صلاحية تعديل نتيجة.
     * EN: Whether the user can update a result.
     */
    public function update(User $user, TestResult $result): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->hasRole(['Admin', 'Examiner']) && $this->resultInUserScope($user, $result);
    }

    /**
     * صلاحية حذف نتيجة.
     * EN: Whether the user can delete a result.
     */
    public function delete(User $user, TestResult $result): bool
    {
        return $this->update($user, $result);
    }

    /**
     * تقييد استعلام النتائج حسب المستخدم الحالي.
     *
     * Arabic: SuperAdmin غير مقيّد. غير ذلك: يقتصر على النتائج المرتبطة بتعيينات ضمن حلقات
     * تابعة لمراكز المستخدم المُدارة.
     * EN: Scopes results to assignments in halaqahs within the user's managed centers.
     */
    public function scopeQueryForUser(User $user, Builder $query): Builder
    {
        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        $centerIds = $user->managedCenters()->pluck('id');

        return $query->whereHas('assignment.halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $centerIds));
    }

    /**
     * التحقق أن النتيجة ضمن نطاق المستخدم.
     * EN: Checks whether a result belongs to a halaqah in the user's managed centers.
     */
    private function resultInUserScope(User $user, TestResult $result): bool
    {
        $centerIds = $user->managedCenters()->pluck('id');
        return $result->assignment()->whereHas('halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $centerIds))->exists();
    }
}

