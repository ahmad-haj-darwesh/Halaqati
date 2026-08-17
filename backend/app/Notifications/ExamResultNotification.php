<?php

namespace App\Notifications;

use App\Models\TestResult;
use App\Services\NotificationService;

/**
 * إشعار FCM لمعلّم الحلقة عند تسجيل نتيجة اختبار.
 *
 * Arabic: يستخرج الطالب والمعلّم من `TestAssignment` عبر الحلقة، ويُرسل ملخصاً
 * بالمجموع والمستوى اللفظي للعرض.
 * EN: Notifies the halaqah teacher when a student's exam result is saved.
 */
class ExamResultNotification
{
    public function __construct(private NotificationService $fcm) {}

    /**
     * إشعار معلّم الحلقة عند تسجيل نتيجة اختبار لأحد طلابه.
     *
     * EN: Sends a push with total score and Arabic level label; no-op if links missing.
     */
    public function notifyTeacher(TestResult $result): void
    {
        $result->loadMissing('assignment.student', 'assignment.halaqah.teacherProfile.user');

        $student = $result->assignment?->student;
        $teacher = $result->assignment?->halaqah?->teacherProfile?->user;

        if (! $student || ! $teacher) {
            return;
        }

        $total = (int) $result->total_score;

        $level = match (true) {
            $total >= 90 => 'ممتاز',
            $total >= 75 => 'جيد جداً',
            $total >= 60 => 'جيد',
            $total >= 50 => 'مقبول',
            default => 'ضعيف',
        };

        $this->fcm->sendToUser(
            $teacher,
            'نتيجة اختبار طالب',
            "الطالب {$student->full_name} حصل على {$total}/100 — {$level}",
            [
                'type' => 'exam_result',
                'student_id' => (string) $student->id,
                'result_id' => (string) $result->id,
            ]
        );
    }
}
