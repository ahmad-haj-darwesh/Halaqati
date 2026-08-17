<?php

namespace App\Policies;

use App\Models\Center;
use App\Models\SupervisoryVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * سياسة صلاحيات الزيارات الإشرافية (SupervisoryVisitPolicy).
 *
 * Arabic: تتحكم في صلاحيات عرض/إنشاء/تعديل/حذف الزيارات الإشرافية، مع قواعد مهمة:
 * - الزيارة المعتمدة (is_finalized) لا تُعدّل إلا بواسطة SuperAdmin\n+ * - المشرف التربوي يمكنه تعديل زياراته فقط ضمن مراكزه\n+ * - نطاق العرض يقتصر على المراكز المُدارة، مع تضييق إضافي للمشرف التربوي على زياراته
 *
 * EN: Authorization policy for supervisory visits with finalized restrictions and managed-centers scoping.
 */
class SupervisoryVisitPolicy
{
    /**
     * صلاحية عرض قائمة الزيارات.
     * EN: Whether the user can view any visits.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'EducationalSupervisor']);
    }

    /**
     * صلاحية عرض زيارة محددة.
     * EN: Whether the user can view a specific visit.
     */
    public function view(User $user, SupervisoryVisit $visit): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole(['Admin', 'EducationalSupervisor'])) {
            return $this->centerInScope($user, $visit->center_id);
        }

        return false;
    }

    /**
     * صلاحية إنشاء زيارة.
     * EN: Whether the user can create visits.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'EducationalSupervisor']);
    }

    /**
     * صلاحية تعديل زيارة.
     *
     * Arabic: إذا كانت الزيارة معتمدة، التعديل محصور بـ SuperAdmin فقط.
     * EN: Finalized visits are editable only by SuperAdmin.
     */
    public function update(User $user, SupervisoryVisit $visit): bool
    {
        if ($visit->is_finalized) {
            return $user->hasRole('SuperAdmin');
        }

        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole('EducationalSupervisor')) {
            return $this->centerInScope($user, $visit->center_id) && $visit->supervisor_user_id === $user->id;
        }

        return false;
    }

    /**
     * صلاحية حذف زيارة.
     *
     * Arabic: الزيارة المعتمدة لا تُحذف إلا بواسطة SuperAdmin.
     * EN: Finalized visits can only be deleted by SuperAdmin.
     */
    public function delete(User $user, SupervisoryVisit $visit): bool
    {
        if ($visit->is_finalized) {
            return $user->hasRole('SuperAdmin');
        }

        return $user->hasRole('SuperAdmin');
    }

    /**
     * تقييد استعلام الزيارات حسب المستخدم الحالي.
     *
     * Arabic: يقيّد حسب المراكز المُدارة، ويضيف قيداً إضافياً للمشرف التربوي ليرى زياراته فقط.
     * EN: Scopes visits query by managed centers; EducationalSupervisor sees own visits.
     */
    public function scopeQueryForUser(User $user, Builder $query): Builder
    {
        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        $centerIds = $user->managedCenters()->pluck('id');
        $query = $query->whereIn('center_id', $centerIds);

        if ($user->hasRole('EducationalSupervisor')) {
            $query->where('supervisor_user_id', $user->id);
        }

        return $query;
    }

    /**
     * التحقق أن المركز ضمن نطاق المستخدم (مراكزه المُدارة).
     * EN: Checks whether a center is in the user's managed centers.
     */
    private function centerInScope(User $user, int $centerId): bool
    {
        return $user->managedCenters()->whereKey($centerId)->exists();
    }
}

