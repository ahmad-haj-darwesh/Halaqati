<?php

namespace App\Support;

use App\Models\TestResult;

/**
 * حساب المجموع وربط المستوى العربي بنظام التخزين (enum) في test_results.
 *
 * Arabic: يوفّر دوال مساعدة لاحتساب مجموع الدرجات وترجمة المستوى إلى قيمة مخزّنة
 * (enum) وإلى تسمية عربية لواجهات العرض.
 * EN: Helpers for computing totals and mapping score levels (enum + Arabic labels).
 */
final class ExaminerScore
{
    /**
     * احتساب المجموع الكلي من مكونات الدرجة وفق أوزان ثابتة.
     *
     * Arabic: الأوزان الحالية: حفظ 50%، تجويد 30%، مراجعة 20%.
     * EN: Weighted total: memorization 50%, tajweed 30%, review 20%.
     */
    public static function totalFromComponents(int $memorization, int $tajweed, int $review): int
    {
        return (int) round(
            ($memorization * 0.5) + ($tajweed * 0.3) + ($review * 0.2)
        );
    }

    /**
     * تحويل المجموع الكلي إلى قيمة enum للتخزين (TestResult::LEVEL_*).
     *
     * Arabic: يطابق حدود المستويات المعرّفة في المنطق الحالي.
     * EN: Maps total score to stored enum level.
     */
    public static function levelEnumFromTotal(int $total): string
    {
        return match (true) {
            $total >= 90 => TestResult::LEVEL_EXCELLENT,
            $total >= 75 => TestResult::LEVEL_GOOD,
            $total >= 60 => TestResult::LEVEL_ACCEPTABLE,
            default => TestResult::LEVEL_WEAK,
        };
    }

    /**
     * تحويل المجموع إلى تسمية عربية للعرض في واجهات الـ API.
     *
     * Arabic: قد تختلف هذه التسميات عن enum المخزّن لأنها موجهة لواجهة المستخدم.
     * EN: Returns an Arabic label for UI/API display.
     */
    public static function arabicLabelFromTotal(int $total): string
    {
        return match (true) {
            $total >= 90 => 'ممتاز',
            $total >= 75 => 'جيد جداً',
            $total >= 60 => 'جيد',
            $total >= 50 => 'مقبول',
            default => 'ضعيف',
        };
    }

    /**
     * تحويل enum المخزّن إلى تسمية عربية (إن وُجدت).
     *
     * Arabic: يعيد `null` عند قيم غير معروفة.
     * EN: Maps stored enum level to an Arabic label (or null if unknown).
     */
    public static function arabicLabelFromEnum(?string $level): ?string
    {
        return match ($level) {
            TestResult::LEVEL_EXCELLENT => 'ممتاز',
            TestResult::LEVEL_GOOD => 'جيد جداً',
            TestResult::LEVEL_ACCEPTABLE => 'جيد',
            TestResult::LEVEL_WEAK => 'ضعيف',
            default => null,
        };
    }
}
