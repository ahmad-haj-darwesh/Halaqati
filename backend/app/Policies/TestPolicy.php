<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * سياسة صلاحيات الاختبارات (TestPolicy).
 *
 * Arabic: تتحكم في صلاحيات عرض/إنشاء/تعديل/حذف الاختبارات، وتوفّر دالة `scopeQueryForUser`
 * لتقييد استعلامات الاختبارات حسب نطاق المستخدم (المراكز المُدارة) عبر:
 * - نطاق الاختبار (حلقة/مركز/منطقة)\n+ * - أو وجود تعيينات داخل حلقات ضمن مراكز المستخدم
 *
 * EN: Authorization policy for tests with a robust query scope based on managed centers and actual assignments.
 */
class TestPolicy
{
    /**
     * صلاحية عرض قائمة الاختبارات.
     * EN: Whether the user can view any tests.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'Examiner']);
    }

    /**
     * صلاحية عرض اختبار محدد.
     * EN: Whether the user can view a specific test.
     */
    public function view(User $user, Test $test): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $this->testInUserScope($user, $test);
        }

        if ($user->hasRole('Examiner')) {
            return $this->testInUserScope($user, $test);
        }

        return false;
    }

    /**
     * صلاحية إنشاء اختبار.
     * EN: Whether the user can create tests.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'Examiner']);
    }

    /**
     * صلاحية تعديل اختبار.
     * EN: Whether the user can update a test.
     */
    public function update(User $user, Test $test): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->hasRole(['Admin', 'Examiner']) && $this->testInUserScope($user, $test);
    }

    /**
     * صلاحية حذف اختبار.
     * EN: Whether the user can delete a test.
     */
    public function delete(User $user, Test $test): bool
    {
        return $this->update($user, $test);
    }

    /**
     * تقييد استعلام الاختبارات حسب المستخدم الحالي.
     *
     * Arabic: SuperAdmin غير مقيّد. غير ذلك: يقتصر على الاختبارات ذات النطاق المرتبط بمراكز المستخدم
     * أو التي لديها تعيينات ضمن حلقات في مراكز المستخدم.
     * EN: Scopes tests query to those within user's managed centers (by scope or assignments).
     */
    public function scopeQueryForUser(User $user, Builder $query): Builder
    {
        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        $centerIds = $user->managedCenters()->pluck('id');

        if ($centerIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $outer) use ($centerIds) {
            // نطاق الاختبار نفسه (حلقة / مركز / منطقة)
            $outer->where(function (Builder $q) use ($centerIds) {
                $q->whereHas('scopeHalaqah', fn (Builder $hq) => $hq->whereIn('center_id', $centerIds))
                    ->orWhereIn('scope_center_id', $centerIds)
                    ->orWhereHas('scopeRegion', fn (Builder $rq) => $rq->whereHas('centers', fn (Builder $cq) => $cq->whereIn('id', $centerIds)));
            })
                // أو يوجد تعيينات لطلاب في حلقات ضمن مراكز المختبر (مفيد عند اختلاف إعداد نطاق الاختبار عن التعيينات الفعلية)
                ->orWhereHas('assignments', function (Builder $aq) use ($centerIds) {
                    $aq->whereHas('halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $centerIds));
                });
        });
    }

    /**
     * التحقق أن الاختبار ضمن نطاق المستخدم (وفق `scopeQueryForUser`).
     * EN: Checks whether a test is in user scope by reusing scopeQueryForUser.
     */
    private function testInUserScope(User $user, Test $test): bool
    {
        $query = Test::query()->whereKey($test->getKey());

        return $this->scopeQueryForUser($user, $query)->exists();
    }
}

