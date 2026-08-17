<?php

namespace App\Support;

use App\Models\User;

/**
 * أدوار مسموحة لتطبيق الجوال (Flutter).
 *
 * Arabic: طبقة توحيد بين نظام الأدوار في الباكند (Spatie) وبين الأدوار التي
 * يتوقعها تطبيق Flutter عند اختيار الشاشة الرئيسية.
 * EN: Maps backend roles to the mobile app's expected role identifiers.
 */
final class MobileAppRole
{
    public const TEACHER = 'Teacher';

    public const EXAMINER = 'Examiner';

    public const CENTER_SUPERVISOR = 'CenterSupervisor';

    /**
     * استخراج الدور المناسب للموبايل من أدوار المستخدم.
     *
     * Arabic: يعيد أول دور مطابق حسب الأولوية (Teacher ثم Examiner ثم CenterSupervisor).
     * EN: Resolves the first matching mobile role for the given user.
     */
    public static function resolve(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if ($user->hasRole(self::TEACHER)) {
            return self::TEACHER;
        }

        if ($user->hasRole(self::EXAMINER)) {
            return self::EXAMINER;
        }

        if ($user->hasRole(self::CENTER_SUPERVISOR)) {
            return self::CENTER_SUPERVISOR;
        }

        return null;
    }
}
