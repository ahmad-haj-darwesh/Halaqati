<?php

namespace App\Policies;

use App\Models\TeacherProfile;
use App\Models\User;

/**
 * سياسة صلاحيات ملفات المعلّمين (TeacherProfilePolicy).
 *
 * Arabic: تسمح للأدوار الإدارية/الإشرافية بعرض ملفات المعلّمين، وتقيّد الوصول حسب
 * نطاق المراكز المُدارة للمستخدم (خصوصاً لـ Admin والمشرفين).
 * EN: Authorization policy for teacher profiles with managed-centers scoping.
 */
class TeacherProfilePolicy
{
    /**
     * صلاحية عرض قائمة ملفات المعلّمين.
     * EN: Whether the user can view any teacher profiles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin', 'EducationalSupervisor', 'CenterSupervisor']);
    }

    /**
     * صلاحية عرض ملف معلّم محدد.
     * EN: Whether the user can view a specific teacher profile.
     */
    public function view(User $user, TeacherProfile $teacherProfile): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        $centerId = $teacherProfile->halaqah?->center_id;

        if ($user->hasRole('Admin')) {
            return $centerId && $user->managedCenters()->where('id', $centerId)->exists();
        }

        if ($user->hasRole(['EducationalSupervisor', 'CenterSupervisor'])) {
            return $centerId && $user->managedCenters()->where('id', $centerId)->exists();
        }

        return false;
    }

    /**
     * صلاحية إنشاء ملف معلّم.
     * EN: Whether the user can create teacher profiles.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية تعديل ملف معلّم.
     * EN: Whether the user can update a teacher profile.
     */
    public function update(User $user, TeacherProfile $teacherProfile): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        $centerId = $teacherProfile->halaqah?->center_id;

        return $user->hasRole('Admin')
            && $centerId
            && $user->managedCenters()->where('id', $centerId)->exists();
    }

    /**
     * صلاحية حذف ملف معلّم.
     * EN: Whether the user can delete a teacher profile.
     */
    public function delete(User $user, TeacherProfile $teacherProfile): bool
    {
        return $this->update($user, $teacherProfile);
    }
}
