<?php

namespace App\Console\Commands;

use App\Notifications\HalaqahNotRecordedNotification;
use Illuminate\Console\Command;

/**
 * أمر Artisan: تذكير الحضور للحلقات غير المسجّلة اليوم.
 *
 * Arabic: يستدعي `HalaqahNotRecordedNotification::notifySupervisors()` لإرسال FCM
 * للمعلّمين والمشرفين حسب المنطق المعرف في الخدمة.
 * EN: Console entry point for scheduled unrecorded-halaqah push notifications.
 */
class NotifyUnrecordedHalaqahs extends Command
{
    protected $signature = 'notify:unrecorded-halaqahs';

    protected $description = 'إرسال إشعارات للمعلّمين والمشرفين عن الحلقات غير المسجّلة';

    /**
     * تنفيذ الأمر: تشغيل مسار الإشعارات وطباعة حالة بسيطة.
     *
     * EN: Delegates to notification service and returns success exit code.
     */
    public function handle(HalaqahNotRecordedNotification $notification): int
    {
        $this->info('جاري التحقق من الحلقات غير المسجّلة...');
        $notification->notifySupervisors();
        $this->info('تم تنفيذ المهمة.');

        return self::SUCCESS;
    }
}
