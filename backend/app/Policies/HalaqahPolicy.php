<?php

namespace App\Policies;

use App\Models\Halaqah;
use App\Models\User;

/**
 * سياسة صلاحيات الحلقات (HalaqahPolicy).
 *
 * Arabic: تتحكم في صلاحيات عرض/إنشاء/تعديل/حذف الحلقات حسب دور المستخدم ونطاق مراكزه المُدارة.
 * EN: Authorization policy for halaqahs, scoped by managed centers.
 */
class HalaqahPolicy
{
    /**
     * صلاحية عرض قائمة الحلقات.
     * EN: Whether the user can view any halaqahs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'EducationalSupervisor', 'CenterSupervisor', 'Examiner']);
    }

    /**
     * صلاحية عرض حلقة محددة.
     * EN: Whether the user can view a specific halaqah.
     */
    public function view(User $user, Halaqah $halaqah): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $user->managedCenters()->where('id', $halaqah->center_id)->exists();
        }

        if ($user->hasRole(['EducationalSupervisor', 'CenterSupervisor', 'Examiner'])) {
            return $user->managedCenters()->where('id', $halaqah->center_id)->exists();
        }

        return false;
    }

    /**
     * صلاحية إنشاء حلقة.
     * EN: Whether the user can create halaqahs.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية تعديل حلقة.
     * EN: Whether the user can update a halaqah.
     */
    public function update(User $user, Halaqah $halaqah): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->hasRole('Admin')
            && $user->managedCenters()->where('id', $halaqah->center_id)->exists();
    }

    /**
     * صلاحية حذف حلقة.
     * EN: Whether the user can delete a halaqah.
     */
    public function delete(User $user, Halaqah $halaqah): bool
    {
        return $this->update($user, $halaqah);
    }
}
