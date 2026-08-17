<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * مساعدات المزامنة المؤجلة لتطبيق الجوال (Offline-first).
 *
 * Arabic: يجمع في مكان واحد المنطق المشترك بين نقاط الكتابة الثلاث: حدود نافذة
 * التواريخ المسموح بها، وتفسير الطابع الزمني القادم من الجهاز (وهو غير موثوق لأن
 * ساعة الجهاز قابلة للتعديل)، وبناء أعمدة التدقيق التي تُحفظ مع كل سجل.
 * EN: Shared helpers for offline sync: date window bounds, untrusted client clock
 * handling, and the audit columns persisted with every synced record.
 */
class OfflineSync
{
    /**
     * أقدم تاريخ يقبل الخادم استلام سجلات عنه.
     * EN: Oldest date the API still accepts records for.
     */
    public static function windowStart(?Carbon $now = null): Carbon
    {
        $now = $now ? $now->copy() : Carbon::now();

        return $now->startOfDay()->subDays(self::windowDays());
    }

    /**
     * عدد أيام النافذة (لا يقل عن صفر = اليوم فقط).
     * EN: Configured window size in days, never negative.
     */
    public static function windowDays(): int
    {
        return max(0, (int) config('offline.sync_window_days', 7));
    }

    /**
     * هل يقع التاريخ ضمن النافذة المسموح بها؟ (لا ماضٍ بعيد ولا مستقبل)
     * EN: Whether the given date falls inside the accepted sync window.
     */
    public static function isDateWithinWindow(string $date, ?Carbon $now = null): bool
    {
        $now = $now ? $now->copy() : Carbon::now();

        try {
            $target = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            // تاريخ غير قابل للتفسير يُعامَل كخارج النافذة بدل تفجير استثناء 500.
            return false;
        }

        return $target->gte(self::windowStart($now)) && $target->lte($now->copy()->startOfDay());
    }

    /**
     * رسالة الرفض عند خروج التاريخ عن النافذة.
     * EN: Localized rejection message for out-of-window dates.
     */
    public static function outOfWindowMessage(): string
    {
        $days = self::windowDays();

        if ($days === 0) {
            return 'يمكن التسجيل ليوم اليوم فقط.';
        }

        return "يمكن التسجيل لليوم الحالي أو حتى {$days} أيام سابقة فقط. تعذّرت مزامنة سجلات أقدم من ذلك.";
    }

    /**
     * تفسير الطابع الزمني القادم من الجهاز مع كبح انحراف الساعة.
     *
     * Arabic: يُرجع `null` إذا كانت القيمة غائبة أو غير صالحة أو في المستقبل بأكثر
     * من الحد المسموح — والمستدعي يعود عندها لوقت الخادم. هذا يمنع مستخدماً من
     * إطالة نافذة تعديل التقييم بتقديم ساعة جهازه.
     * EN: Parses an untrusted client timestamp, rejecting far-future values.
     */
    public static function clientTimestamp(mixed $raw, ?Carbon $now = null): ?Carbon
    {
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            $parsed = Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }

        $now = $now ? $now->copy() : Carbon::now();
        $skew = max(0, (int) config('offline.max_client_clock_skew_minutes', 10));

        if ($parsed->gt($now->copy()->addMinutes($skew))) {
            return null;
        }

        return $parsed;
    }

    /**
     * أعمدة التدقيق التي تُدمج في حمولة الحفظ.
     *
     * Arabic: `synced_at` دائماً وقت الخادم (موثوق)، و`client_recorded_at` وقت
     * الجهاز بعد الكبح — أو وقت الخادم عند غيابه (حفظ مباشر وهو متصل).
     * EN: Audit columns merged into every synced write.
     *
     * @return array{client_recorded_at: Carbon, synced_at: Carbon}
     */
    public static function auditColumns(mixed $rawClientTime, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy() : Carbon::now();

        return [
            'client_recorded_at' => self::clientTimestamp($rawClientTime, $now) ?? $now->copy(),
            'synced_at' => $now->copy(),
        ];
    }
}
