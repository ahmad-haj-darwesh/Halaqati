<?php

namespace App\Policies;

use App\Models\Center;
use App\Models\User;

/**
 * سياسة صلاحيات المراكز (CenterPolicy).
 *
 * Arabic: تحدد من يمكنه عرض/إنشاء/تعديل/حذف المراكز. SuperAdmin يمتلك صلاحيات كاملة،
 * بينما Admin يقتصر على مراكزه (أو ما يديره)، وباقي الأدوار الإشرافية ضمن حدود معينة.
 * EN: Authorization policy for centers with SuperAdmin full access and managed-centers restrictions for others.
 */
class CenterPolicy
{
    /**
     * صلاحية عرض قائمة المراكز.
     * EN: Whether the user can view any centers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'EducationalSupervisor', 'CenterSupervisor']);
    }

    /**
     * صلاحية عرض مركز محدد.
     * EN: Whether the user can view a specific center.
     */
    public function view(User $user, Center $center): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $center->admin_user_id === $user->id
                || $user->managedCenters()->where('id', $center->id)->exists();
        }

        if ($user->hasRole(['EducationalSupervisor', 'CenterSupervisor'])) {
            return $center->admin_user_id === $user->id;
        }

        return false;
    }

    /**
     * صلاحية إنشاء مركز.
     * EN: Whether the user can create centers.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية تعديل مركز.
     * EN: Whether the user can update a center.
     */
    public function update(User $user, Center $center): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->hasRole('Admin') && $center->admin_user_id === $user->id;
    }

    /**
     * صلاحية حذف مركز.
     * EN: Whether the user can delete a center.
     */
    public function delete(User $user, Center $center): bool
    {
        return $this->update($user, $center);
    }
}
