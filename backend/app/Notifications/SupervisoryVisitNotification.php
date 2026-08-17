<?php

namespace App\Notifications;

use App\Models\SupervisorFieldVisit;
use App\Services\NotificationService;

/**
 * إشعار FCM للمعلّم عند تسجيل زيارة ميدانية (SupervisorFieldVisit).
 *
 * Arabic: يبني رسالة عربية مختصرة تحتوي التاريخ ومتوسط الدرجات الثلاث، ومرفقات
 * بيانات للتنقل داخل التطبيق.
 * EN: Sends a push to the teacher when a field visit record is created.
 */
class SupervisoryVisitNotification
{
    public function __construct(private NotificationService $fcm) {}

    /**
     * إشعار المعلّم عند تسجيل زيارة ميدانية (حقل) من المشرف.
     *
     * EN: Notifies the halaqah teacher with average score and deep-link payload.
     */
    public function notifyTeacher(SupervisorFieldVisit $visit): void
    {
        $visit->loadMissing('teacher');
        $teacher = $visit->teacher;
        if (! $teacher) {
            return;
        }

        $avgScore = round(
            ($visit->teaching_skill_score +
                $visit->plan_adherence_score +
                $visit->student_engagement_score) / 3,
            1
        );

        $this->fcm->sendToUser(
            $teacher,
            'زيارة إشرافية جديدة',
            'تم تسجيل زيارة إشرافية بتاريخ '.$visit->visit_date->format('Y-m-d')." — المتوسط: {$avgScore}/10",
            [
                'type' => 'supervisory_visit',
                'visit_id' => (string) $visit->id,
            ]
        );
    }
}
