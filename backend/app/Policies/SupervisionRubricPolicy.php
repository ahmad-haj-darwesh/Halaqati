<?php

namespace App\Policies;

use App\Models\SupervisionRubric;
use App\Models\User;

/**
 * سياسة صلاحيات قوالب الإشراف (SupervisionRubricPolicy).
 *
 * Arabic: تسمح بعرض القوالب وإدارتها للأدوار العليا (SuperAdmin/Admin)، بينما
 * الحذف محصور بـ SuperAdmin فقط لتقليل مخاطر فقدان قوالب التقييم.
 * EN: Authorization policy for supervision rubrics (write for SuperAdmin/Admin; delete for SuperAdmin only).
 */
class SupervisionRubricPolicy
{
    /**
     * صلاحية عرض قائمة القوالب.
     * EN: Whether the user can view any rubrics.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية عرض قالب محدد.
     * EN: Whether the user can view a specific rubric.
     */
    public function view(User $user, SupervisionRubric $rubric): bool
    {
        return $this->viewAny($user);
    }

    /**
     * صلاحية إنشاء قالب.
     * EN: Whether the user can create rubrics.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية تعديل قالب.
     * EN: Whether the user can update rubrics.
     */
    public function update(User $user, SupervisionRubric $rubric): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية حذف قالب.
     * EN: Whether the user can delete rubrics.
     */
    public function delete(User $user, SupervisionRubric $rubric): bool
    {
        return $user->hasRole('SuperAdmin');
    }
}

