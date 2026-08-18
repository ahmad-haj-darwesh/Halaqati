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
     * Arabic: SuperAdmin يقرأ كل شيء لكنه يعدّل حسابات Admin فقط — لا يعدّل المعلمين
     * ولا المختبِرين ولا المشرفين (هؤلاء يديرهم Admin ضمن نطاقه).
     * Admin لا يعدّل حسابات SuperAdmin/Admin.
     * EN: SuperAdmin may update Admin accounts only; Admin cannot update SuperAdmin/Admin accounts.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        if ($authUser->hasRole('SuperAdmin')) {
            return $targetUser->hasRole('Admin');
        }

        if ($authUser->hasRole('Admin')) {
            return ! $targetUser->hasRole(['SuperAdmin', 'Admin']);
        }

        return false;
    }

    /**
     * صلاحية حذف مستخدم.
     *
     * Arabic: SuperAdmin يحذف حسابات Admin فقط، ولا يحذف حسابه الحالي لتجنب قفل اللوحة.
     * Admin لا يحذف حسابات SuperAdmin/Admin.
     * EN: SuperAdmin may delete Admin accounts only (never self); Admin cannot delete high-privilege accounts.
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        if ($authUser->hasRole('SuperAdmin')) {
            // منع حذف الحساب الحالي لتجنب قفل لوحة الإدارة بالخطأ
            return $authUser->id !== $targetUser->id
                && $targetUser->hasRole('Admin');
        }

        if ($authUser->hasRole('Admin')) {
            return ! $targetUser->hasRole(['SuperAdmin', 'Admin']);
        }

        return false;
    }
}
