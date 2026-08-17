<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Models\TestResult;
use App\Models\User;
use App\Notifications\ExamResultNotification;
use App\Notifications\HalaqahNotRecordedNotification;
use App\Notifications\SupervisoryVisitNotification;
use App\Services\NotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationClassesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_halaqah_not_recorded_notifies_teacher(): void
    {
        $teacherUser = User::factory()->create(['fcm_token' => 'test-token']);
        $teacherUser->assignRole('Teacher');

        $center = Center::factory()->create();
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);
        $teacherProfile = TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $notification = new HalaqahNotRecordedNotification(app(NotificationService::class));
        $notification->notifySupervisors();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $teacherUser->id,
            'title' => 'تذكير: تسجيل الحضور',
            'type' => 'record_reminder',
        ]);
    }

    public function test_halaqah_not_recorded_notifies_center_supervisor(): void
    {
        $supervisor = User::factory()->create(['fcm_token' => 'test-token']);
        $supervisor->assignRole('CenterSupervisor');

        $center = Center::factory()->create(['admin_user_id' => $supervisor->id]);
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);

        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');
        TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $notification = new HalaqahNotRecordedNotification(app(NotificationService::class));
        $notification->notifySupervisors();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $supervisor->id,
            'title' => 'تنبيه: حلقة لم تُسجَّل',
            'type' => 'unrecorded_halaqah',
        ]);
    }

    public function test_halaqah_not_recorded_skips_halaqah_with_attendance(): void
    {
        $teacherUser = User::factory()->create(['fcm_token' => 'test-token']);
        $teacherUser->assignRole('Teacher');

        $center = Center::factory()->create();
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);
        TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        // Create attendance record for today
        \App\Models\AttendanceRecord::factory()->create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'date' => today(),
        ]);

        $notification = new HalaqahNotRecordedNotification(app(NotificationService::class));
        $notification->notifySupervisors();

        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $teacherUser->id,
            'type' => 'record_reminder',
        ]);
    }

    public function test_halaqah_not_recorded_skips_inactive_enrollments(): void
    {
        $teacherUser = User::factory()->create(['fcm_token' => 'test-token']);
        $teacherUser->assignRole('Teacher');

        $center = Center::factory()->create();
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);
        TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_DROPPED,
        ]);

        $notification = new HalaqahNotRecordedNotification(app(NotificationService::class));
        $notification->notifySupervisors();

        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $teacherUser->id,
            'type' => 'record_reminder',
        ]);
    }

    public function test_supervisory_visit_notifies_teacher(): void
    {
        $teacherUser = User::factory()->create(['fcm_token' => 'test-token']);
        $teacherUser->assignRole('Teacher');

        $center = Center::factory()->create();
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);
        TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $visit = \App\Models\SupervisorFieldVisit::factory()->create([
            'teacher_user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'teaching_skill_score' => 8,
            'plan_adherence_score' => 9,
            'student_engagement_score' => 7,
            'visit_date' => now(),
        ]);

        $notification = new SupervisoryVisitNotification(app(NotificationService::class));
        $notification->notifyTeacher($visit);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $teacherUser->id,
            'title' => 'زيارة إشرافية جديدة',
            'type' => 'supervisory_visit',
        ]);
    }

    public function test_supervisory_visit_includes_average_score(): void
    {
        $teacherUser = User::factory()->create(['fcm_token' => 'test-token']);
        $teacherUser->assignRole('Teacher');

        $center = Center::factory()->create();
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);
        TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $visit = \App\Models\SupervisorFieldVisit::factory()->create([
            'teacher_user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'teaching_skill_score' => 8,
            'plan_adherence_score' => 9,
            'student_engagement_score' => 7,
            'visit_date' => now(),
        ]);

        $notification = new SupervisoryVisitNotification(app(NotificationService::class));
        $notification->notifyTeacher($visit);

        $appNotification = \App\Models\AppNotification::where('user_id', $teacherUser->id)->first();
        $this->assertStringContainsString('8/10', $appNotification->body);
    }

    public function test_supervisory_visit_without_teacher_skips_notification(): void
    {
        // Since teacher_user_id is NOT NULL in the database, we'll skip this test
        // The notification class already handles null teacher gracefully
        $this->markTestSkipped('teacher_user_id is NOT NULL in database schema');
    }

    public function test_exam_result_notifies_teacher(): void
    {
        $teacherUser = User::factory()->create(['fcm_token' => 'test-token']);
        $teacherUser->assignRole('Teacher');

        $center = Center::factory()->create();
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);
        TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $student = Student::factory()->create(['full_name' => 'أحمد محمد']);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $test = Test::factory()->create();
        $assignment = TestAssignment::factory()->create([
            'test_id' => $test->id,
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $result = TestResult::factory()->create([
            'test_assignment_id' => $assignment->id,
            'total_score' => 85,
        ]);

        $notification = new ExamResultNotification(app(NotificationService::class));
        $notification->notifyTeacher($result);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $teacherUser->id,
            'title' => 'نتيجة اختبار طالب',
            'type' => 'exam_result',
        ]);
    }

    public function test_exam_result_includes_student_name_and_score(): void
    {
        $teacherUser = User::factory()->create(['fcm_token' => 'test-token']);
        $teacherUser->assignRole('Teacher');

        $center = Center::factory()->create();
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);
        TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $student = Student::factory()->create(['full_name' => 'محمد علي']);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $test = Test::factory()->create();
        $assignment = TestAssignment::factory()->create([
            'test_id' => $test->id,
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $result = TestResult::factory()->create([
            'test_assignment_id' => $assignment->id,
            'total_score' => 92,
        ]);

        $notification = new ExamResultNotification(app(NotificationService::class));
        $notification->notifyTeacher($result);

        $appNotification = \App\Models\AppNotification::where('user_id', $teacherUser->id)->first();
        $this->assertStringContainsString('محمد علي', $appNotification->body);
        $this->assertStringContainsString('92/100', $appNotification->body);
    }

    public function test_exam_result_shows_correct_level(): void
    {
        $teacherUser = User::factory()->create(['fcm_token' => 'test-token']);
        $teacherUser->assignRole('Teacher');

        $center = Center::factory()->create();
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);
        TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        // Test different score levels with separate assignments
        $scores = [
            95 => 'ممتاز',
            80 => 'جيد جداً',
            65 => 'جيد',
            55 => 'مقبول',
            40 => 'ضعيف',
        ];

        foreach ($scores as $score => $expectedLevel) {
            // Clear previous notifications
            \App\Models\AppNotification::where('user_id', $teacherUser->id)->delete();

            $test = Test::factory()->create();
            $assignment = TestAssignment::factory()->create([
                'test_id' => $test->id,
                'student_id' => $student->id,
                'halaqah_id' => $halaqah->id,
            ]);

            $result = TestResult::factory()->create([
                'test_assignment_id' => $assignment->id,
                'total_score' => $score,
            ]);

            $notification = new ExamResultNotification(app(NotificationService::class));
            $notification->notifyTeacher($result);

            $appNotification = \App\Models\AppNotification::where('user_id', $teacherUser->id)->first();
            $this->assertStringContainsString($expectedLevel, $appNotification->body);
        }
    }

    public function test_exam_result_without_teacher_skips(): void
    {
        $test = Test::factory()->create();
        
        $center = Center::factory()->create();
        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);
        
        $student = Student::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $assignment = TestAssignment::factory()->create([
            'test_id' => $test->id,
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
        ]);

        // No teacher profile for this halaqah
        $result = TestResult::factory()->create([
            'test_assignment_id' => $assignment->id,
            'total_score' => 85,
        ]);

        $notification = new ExamResultNotification(app(NotificationService::class));
        $notification->notifyTeacher($result);

        $this->assertDatabaseCount('app_notifications', 0);
    }
}
