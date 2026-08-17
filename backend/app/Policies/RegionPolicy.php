<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

/**
 * سياسة صلاحيات المناطق (RegionPolicy).
 *
 * Arabic: تعطي صلاحيات القراءة للأدوار الإدارية/الإشرافية، وتقيّد الإنشاء/التعديل/الحذف
 * بالأدوار العليا (SuperAdmin/Admin) فقط.
 * EN: Authorization policy for regions (read for broader roles; write operations for SuperAdmin/Admin only).
 */
class RegionPolicy
{
    /**
     * صلاحية عرض قائمة المناطق.
     * EN: Whether the user can view any regions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'EducationalSupervisor', 'CenterSupervisor']);
    }

    /**
     * صلاحية عرض منطقة محددة.
     * EN: Whether the user can view a specific region.
     */
    public function view(User $user, Region $region): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'EducationalSupervisor', 'CenterSupervisor']);
    }

    /**
     * صلاحية إنشاء منطقة.
     * EN: Whether the user can create regions.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية تعديل منطقة.
     * EN: Whether the user can update regions.
     */
    public function update(User $user, Region $region): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية حذف منطقة.
     * EN: Whether the user can delete regions.
     */
    public function delete(User $user, Region $region): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }
}
