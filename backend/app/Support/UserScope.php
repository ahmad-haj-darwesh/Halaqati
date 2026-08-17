<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * محدد نطاق البيانات حسب صلاحيات المستخدم.
 *
 * Arabic: يوفّر helpers لتقييد الاستعلامات على مراكز/حلقات/طلاب وفق مراكز يديرها
 * المستخدم. يستخدم على نطاق واسع في التقارير ولوحة الإدارة.
 * EN: Applies permission-based scoping to queries (centers/halaqahs/students).
 */
class UserScope
{
    /**
     * قائمة المراكز التي يملك المستخدم صلاحية إدارتها.
     *
     * Arabic: في حالة SuperAdmin تُعاد مجموعة فارغة بمعنى "بدون تقييد" وفق منطق
     * هذا المشروع.
     * EN: Returns managed center IDs; empty means unrestricted for SuperAdmin.
     *
     * @return Collection<int,int>
     */
    public function centerIds(User $user): Collection
    {
        if ($user->hasRole('SuperAdmin')) {
            return collect(); // empty means unrestricted in our helpers
        }

        return $user->managedCenters()->pluck('id')->map(fn ($v) => (int) $v)->values();
    }

    /**
     * تقييد استعلام المراكز على مراكز يديرها المستخدم (أو الكل لـ SuperAdmin).
     *
     * EN: Scopes centers query; non-admin with no centers yields no rows.
     */
    public function applyCentersToCentersQuery(User $user, Builder $query): Builder
    {
        $centerIds = $this->centerIds($user);
        if ($centerIds->isEmpty() && ! $user->hasRole('SuperAdmin')) {
            return $query->whereRaw('1=0');
        }

        return $user->hasRole('SuperAdmin') ? $query : $query->whereIn('id', $centerIds);
    }

    /**
     * تقييد استعلام الحلقات حسب `center_id` ضمن نطاق المستخدم.
     *
     * EN: Scopes halaqahs to managed centers; same empty-center semantics as centers.
     */
    public function applyCentersToHalaqahsQuery(User $user, Builder $query): Builder
    {
        $centerIds = $this->centerIds($user);
        if ($centerIds->isEmpty() && ! $user->hasRole('SuperAdmin')) {
            return $query->whereRaw('1=0');
        }

        return $user->hasRole('SuperAdmin') ? $query : $query->whereIn('center_id', $centerIds);
    }

    /**
     * تقييد استعلام الطلاب عبر حلقاتهم المرتبطة بمراكز المستخدم.
     *
     * EN: Scopes students via enrollments → halaqah → center_id in managed set.
     */
    public function applyCentersToStudentsQuery(User $user, Builder $query): Builder
    {
        $centerIds = $this->centerIds($user);
        if ($centerIds->isEmpty() && ! $user->hasRole('SuperAdmin')) {
            return $query->whereRaw('1=0');
        }

        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        return $query->whereHas('enrollments.halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $centerIds));
    }
}

