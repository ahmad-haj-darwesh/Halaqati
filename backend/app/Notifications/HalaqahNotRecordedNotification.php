<?php

namespace App\Notifications;

use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Services\NotificationService;

/**
 * إشعارات تذكير الحضور للحلقات غير المسجّلة اليوم.
 *
 * Arabic: يستهدف الحلقات التي لها معلّم وتسجيلات نشطة ولا يوجد لها سجل حضور
 * بتاريخ اليوم؛ يُرسل للمعلّم وللمشرف المرتبط بالمركز عند توفره.
 * EN: Scheduled reminder FCM for teachers and center supervisors on unrecorded halaqahs.
 */
class HalaqahNotRecordedNotification
{
    public function __construct(private NotificationService $fcm) {}

    /**
     * يُستدعى من المجدول — تنبيه المعلّمين والمشرفين بحلقات بلا تسجيل حضور لليوم.
     *
     * EN: Notifies each affected teacher and the center supervisor (if role matches).
     */
    public function notifySupervisors(): void
    {
        $halaqahs = Halaqah::query()
            ->with(['teacherProfile.user', 'center.admin'])
            ->whereHas('teacherProfile')
            ->whereHas('enrollments', fn ($q) => $q->where('status', Enrollment::STATUS_ACTIVE))
            ->whereDoesntHave('attendanceRecords', fn ($q) => $q->whereDate('date', today()))
            ->get();

        foreach ($halaqahs as $halaqah) {
            $teacherUser = $halaqah->teacherProfile?->user;
            if ($teacherUser) {
                $this->fcm->sendToUser(
                    $teacherUser,
                    'تذكير: تسجيل الحضور',
                    "يرجى تسجيل حضور حلقة «{$halaqah->name}» لليوم",
                    [
                        'type' => 'record_reminder',
                        'halaqah_id' => (string) $halaqah->id,
                    ]
                );
            }

            $admin = $halaqah->center?->admin;
            if ($admin && $admin->hasRole('CenterSupervisor')) {
                $this->fcm->sendToUser(
                    $admin,
                    'تنبيه: حلقة لم تُسجَّل',
                    "حلقة «{$halaqah->name}» في {$halaqah->center?->name} لم يُسجَّل حضورها",
                    [
                        'type' => 'unrecorded_halaqah',
                        'halaqah_id' => (string) $halaqah->id,
                        'center_id' => (string) $halaqah->center_id,
                    ]
                );
            }
        }
    }
}
