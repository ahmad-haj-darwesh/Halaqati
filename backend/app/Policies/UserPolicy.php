<?php

namespace App\Policies;

use App\Models\User;

/**
 * سياسة صلاحيات إدارة المستخدمين (UserPolicy).
 *
 * Arabic: تتحكم في صلاحيات عرض/إنشاء/تعديل/حذف المستخدمين داخل النظام، مع قيود تمنع
 * Admin من تعديل/حذف حسابات SuperAdmin/Admin، وتمنع SuperAdmin من حذف حسابه الحالي.
 * EN: Authorization policy for users with safeguards around high-privilege accounts and self-deletion.
 */
class UserPolicy
{
    /**
     * صلاحية عرض قائمة المستخدمين.
     * EN: Whether the user can view any users.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية عرض مستخدم محدد.
     * EN: Whether the user can view a specific user.
     */
    public function view(User $authUser, User $targetUser): bool
    {
        return $authUser->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية إنشاء مستخدم.
     * EN: Whether the user can create users.
     */
    public function create(User $authUser): bool
    {
        return $authUser->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية تعديل مستخدم.
     *
     * Arabic: SuperAdmin يمكنه التعديل على الجميع. Admin لا يعدّل حسابات SuperAdmin/Admin.
     * EN: SuperAdmin can update anyone; Admin cannot update SuperAdmin/Admin accounts.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        if ($authUser->hasRole('SuperAdmin')) {
            return true;
        }

        if ($authUser->hasRole('Admin')) {
            return ! $targetUser->hasRole(['SuperAdmin', 'Admin']);
        }

        return false;
    }

    /**
     * صلاحية حذف مستخدم.
     *
     * Arabic: SuperAdmin لا يحذف حسابه الحالي لتجنب قفل لوحة الإدارة.
     * EN: SuperAdmin cannot delete self; Admin cannot delete high-privilege accounts.
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        if ($authUser->hasRole('SuperAdmin')) {
            // منع حذف الحساب الحالي لتجنب قفل لوحة الإدارة بالخطأ
            return $authUser->id !== $targetUser->id;
        }

        if ($authUser->hasRole('Admin')) {
            return ! $targetUser->hasRole(['SuperAdmin', 'Admin']);
        }

        return false;
    }
}
