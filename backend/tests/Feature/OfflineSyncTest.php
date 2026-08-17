<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\DailyEvaluation;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\SupervisorFieldVisit;
use App\Models\TeacherProfile;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Models\TestResult;
use App\Models\User;
use Database\Seeders\EvaluationReasonsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * اختبارات دعم العمل دون إنترنت والمزامنة المؤجلة.
 *
 * Arabic: تغطي الحالات التي تنشأ حين يعمل التطبيق بلا شبكة ثم يفرّغ طابوره لاحقاً:
 * تواريخ سابقة، ساعة جهاز غير موثوقة، وإعادة إرسال الطلب نفسه بعد انقطاع.
 * EN: Covers offline-queue drain scenarios: backdated records, untrusted client
 * clocks, and duplicate delivery of the same request after a dropped connection.
 */
class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(EvaluationReasonsSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    /** @return array{0: User, 1: Student, 2: Halaqah, 3: string} */
    private function seedTeacherScope(): array
    {
        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $student = Student::factory()->create(['full_name' => 'طالب المزامنة']);
        Enrollment::create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->subMonth()->toDateString(),
        ]);

        return [$teacher, $student, $halaqah, $teacher->createToken('mobile-app')->plainTextToken];
    }

    // --- سجلات المعلم اليومية: نافذة التواريخ ---

    public function test_teacher_can_sync_records_recorded_on_a_past_day_within_window(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $student, $halaqah, $token] = $this->seedTeacherScope();

        // المعلّم سجّل يوم الأحد بلا إنترنت، والمزامنة تحدث بعد ثلاثة أيام.
        $res = $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-09',
            'records' => [[
                'student_id' => $student->id,
                'attendance_status' => AttendanceRecord::STATUS_PRESENT,
                'client_recorded_at' => '2026-08-09 08:15:00',
            ]],
        ]);

        $res->assertOk();
        $res->assertJsonPath('date', '2026-08-09');

        $record = AttendanceRecord::query()->where('student_id', $student->id)->firstOrFail();
        $this->assertSame('2026-08-09', $record->date);
        $this->assertSame($halaqah->id, $record->halaqah_id);
        $this->assertSame('2026-08-09 08:15:00', $record->client_recorded_at->toDateTimeString());
        $this->assertSame('2026-08-12 20:00:00', $record->synced_at->toDateTimeString());
    }

    public function test_teacher_cannot_sync_records_older_than_the_window(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $student, , $token] = $this->seedTeacherScope();

        // النافذة الافتراضية 7 أيام، وهذا اليوم الثامن.
        $res = $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-04',
            'records' => [[
                'student_id' => $student->id,
                'attendance_status' => AttendanceRecord::STATUS_PRESENT,
            ]],
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors('date');
        $this->assertSame(0, AttendanceRecord::query()->count());
    }

    public function test_teacher_cannot_sync_records_for_a_future_date(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $student, , $token] = $this->seedTeacherScope();

        $res = $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-13',
            'records' => [[
                'student_id' => $student->id,
                'attendance_status' => AttendanceRecord::STATUS_PRESENT,
            ]],
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors('date');
    }

    public function test_resending_the_same_queued_batch_does_not_duplicate_records(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $student, , $token] = $this->seedTeacherScope();

        $payload = [
            'date' => '2026-08-11',
            'records' => [[
                'student_id' => $student->id,
                'attendance_status' => AttendanceRecord::STATUS_PRESENT,
                'memorization_from' => 'البقرة 1',
                'memorization_to' => 'البقرة 10',
                'client_recorded_at' => '2026-08-11 09:00:00',
            ]],
        ];

        // انقطع الاتصال قبل وصول الرد الأول، فأعاد الطابور الإرسال.
        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', $payload)->assertOk();
        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', $payload)->assertOk();

        $this->assertSame(1, AttendanceRecord::query()->where('student_id', $student->id)->count());
    }

    // --- سجلات المعلم اليومية: ساعة الجهاز ونافذة تعديل التقييم ---

    public function test_evaluation_edit_window_is_measured_from_client_time_not_sync_time(): void
    {
        Carbon::setTestNow('2026-08-12 14:00:00');
        [, $student, , $token] = $this->seedTeacherScope();

        // سُجّل التقييم على الجهاز 09:00 وتمت مزامنته 14:00 — النافذة انتهت 10:00.
        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-12',
            'records' => [[
                'student_id' => $student->id,
                'evaluation_overall' => DailyEvaluation::OVERALL_GOOD,
                'client_recorded_at' => '2026-08-12 09:00:00',
            ]],
        ])->assertOk();

        $blocked = $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-12',
            'records' => [[
                'student_id' => $student->id,
                'evaluation_overall' => DailyEvaluation::OVERALL_EXCELLENT,
            ]],
        ]);

        $blocked->assertStatus(422);
        $this->assertSame(
            DailyEvaluation::OVERALL_GOOD,
            DailyEvaluation::query()->where('student_id', $student->id)->value('overall')
        );
    }

    public function test_resyncing_does_not_extend_the_evaluation_edit_window(): void
    {
        Carbon::setTestNow('2026-08-12 09:00:00');
        [, $student, , $token] = $this->seedTeacherScope();

        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-12',
            'records' => [[
                'student_id' => $student->id,
                'evaluation_overall' => DailyEvaluation::OVERALL_GOOD,
                'client_recorded_at' => '2026-08-12 09:00:00',
            ]],
        ])->assertOk();

        // إعادة إرسال بنفس القيم بعد نصف ساعة يجب ألّا تصفّر عدّاد النافذة.
        Carbon::setTestNow('2026-08-12 09:30:00');
        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-12',
            'records' => [[
                'student_id' => $student->id,
                'evaluation_overall' => DailyEvaluation::OVERALL_GOOD,
                'client_recorded_at' => '2026-08-12 09:30:00',
            ]],
        ])->assertOk();

        Carbon::setTestNow('2026-08-12 10:15:00');
        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-12',
            'records' => [[
                'student_id' => $student->id,
                'evaluation_overall' => DailyEvaluation::OVERALL_EXCELLENT,
            ]],
        ])->assertStatus(422);

        $eval = DailyEvaluation::query()->where('student_id', $student->id)->firstOrFail();
        $this->assertSame('2026-08-12 09:00:00', $eval->client_recorded_at->toDateTimeString());
    }

    public function test_client_clock_set_far_in_the_future_is_ignored(): void
    {
        Carbon::setTestNow('2026-08-12 09:00:00');
        [, $student, , $token] = $this->seedTeacherScope();

        // جهاز ساعته مقدَّمة يوماً كاملاً لتمديد نافذة التعديل.
        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-12',
            'records' => [[
                'student_id' => $student->id,
                'evaluation_overall' => DailyEvaluation::OVERALL_GOOD,
                'client_recorded_at' => '2026-08-13 09:00:00',
            ]],
        ])->assertOk();

        $eval = DailyEvaluation::query()->where('student_id', $student->id)->firstOrFail();
        $this->assertSame('2026-08-12 09:00:00', $eval->client_recorded_at->toDateTimeString());
    }

    public function test_today_endpoint_returns_records_for_an_in_window_past_date(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $student, , $token] = $this->seedTeacherScope();

        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => '2026-08-10',
            'records' => [[
                'student_id' => $student->id,
                'attendance_status' => AttendanceRecord::STATUS_EXCUSED,
            ]],
        ])->assertOk();

        $res = $this->withToken($token)->getJson('/api/teacher/halaqah/today?date=2026-08-10');

        $res->assertOk();
        $res->assertJsonPath('date', '2026-08-10');
        $res->assertJsonPath('students.0.attendance.status', AttendanceRecord::STATUS_EXCUSED);

        $this->withToken($token)
            ->getJson('/api/teacher/halaqah/today?date=2026-07-01')
            ->assertStatus(422);
    }

    // --- زيارات المشرف الميدانية: idempotency ---

    /** @return array{0: User, 1: User, 2: Center, 3: string} */
    private function seedSupervisorScope(): array
    {
        $region = Region::create(['name' => 'R']);
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('CenterSupervisor');

        $center = Center::create([
            'name' => 'C',
            'region_id' => $region->id,
            'admin_user_id' => $supervisor->id,
        ]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        return [$supervisor, $teacher, $center, $supervisor->createToken('mobile-app')->plainTextToken];
    }

    /** @return array<string, mixed> */
    private function visitPayload(User $teacher, Center $center, string $uuid): array
    {
        return [
            'client_uuid' => $uuid,
            'client_recorded_at' => '2026-08-11 10:00:00',
            'teacher_id' => $teacher->id,
            'center_id' => $center->id,
            'visit_date' => '2026-08-11',
            'teaching_skill_score' => 8,
            'plan_adherence_score' => 7,
            'student_engagement_score' => 9,
            'notes' => 'ملاحظة',
        ];
    }

    public function test_replaying_a_queued_visit_creates_one_row_and_one_notification(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $teacher, $center, $token] = $this->seedSupervisorScope();
        $uuid = (string) Str::uuid();

        $first = $this->withToken($token)
            ->postJson('/api/supervisor/visits', $this->visitPayload($teacher, $center, $uuid));
        $first->assertCreated();
        $first->assertJsonPath('was_created', true);

        // نفس الطلب يعود من الطابور لأن الرد الأول لم يصل الجهاز.
        $second = $this->withToken($token)
            ->postJson('/api/supervisor/visits', $this->visitPayload($teacher, $center, $uuid));
        $second->assertOk();
        $second->assertJsonPath('was_created', false);
        $second->assertJsonPath('visit_id', $first->json('visit_id'));

        $this->assertSame(1, SupervisorFieldVisit::query()->count());
        $this->assertSame(1, AppNotification::query()->where('user_id', $teacher->id)->count());
    }

    public function test_visit_without_client_uuid_still_works(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $teacher, $center, $token] = $this->seedSupervisorScope();

        $payload = $this->visitPayload($teacher, $center, (string) Str::uuid());
        unset($payload['client_uuid']);

        $this->withToken($token)->postJson('/api/supervisor/visits', $payload)->assertCreated();

        $visit = SupervisorFieldVisit::query()->firstOrFail();
        $this->assertNull($visit->client_uuid);
        $this->assertSame('2026-08-11 10:00:00', $visit->client_recorded_at->toDateTimeString());
    }

    public function test_another_supervisor_cannot_replay_someone_elses_visit_uuid(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $teacher, $center, $token] = $this->seedSupervisorScope();
        $uuid = (string) Str::uuid();

        $this->withToken($token)
            ->postJson('/api/supervisor/visits', $this->visitPayload($teacher, $center, $uuid))
            ->assertCreated();

        $intruder = User::factory()->create(['is_active' => true]);
        $intruder->assignRole('CenterSupervisor');
        $intruderToken = $intruder->createToken('mobile-app')->plainTextToken;

        // الحارس يحتفظ بالمستخدم المُصادَق بين طلبات الاختبار داخل نفس نسخة التطبيق،
        // بينما كل طلب في الإنتاج يبدأ بحارس نظيف — فنُصفّره لنختبر الانتحال فعلاً.
        $this->app['auth']->forgetGuards();

        $this->withToken($intruderToken)
            ->postJson('/api/supervisor/visits', $this->visitPayload($teacher, $center, $uuid))
            ->assertForbidden();

        $this->assertSame(1, SupervisorFieldVisit::query()->count());
    }

    // --- نتائج المختبر ---

    /** @return array{0: User, 1: Student, 2: Test, 3: User, 4: string} */
    private function seedExaminerScope(): array
    {
        $region = Region::create(['name' => 'R']);
        $examiner = User::factory()->create(['is_active' => true]);
        $examiner->assignRole('Examiner');

        $center = Center::create([
            'name' => 'C',
            'region_id' => $region->id,
            'admin_user_id' => $examiner->id,
        ]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $test = Test::create([
            'type' => Test::TYPE_REGULAR,
            'title' => 'اختبار',
            'scope_halaqah_id' => $halaqah->id,
            'scheduled_at' => now(),
            'created_by_user_id' => $examiner->id,
            'is_published' => true,
            'sampling_active_only' => true,
        ]);

        $student = Student::factory()->create();
        Enrollment::create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->subMonth()->toDateString(),
        ]);

        TestAssignment::create([
            'test_id' => $test->id,
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'assigned_at' => now(),
            'assigned_by_user_id' => $examiner->id,
            'status' => TestAssignment::STATUS_ASSIGNED,
        ]);

        return [$examiner, $student, $test, $teacher, $examiner->createToken('mobile-app')->plainTextToken];
    }

    public function test_result_records_the_time_the_test_was_taken_not_the_sync_time(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $student, $test, , $token] = $this->seedExaminerScope();

        $this->withToken($token)->postJson('/api/examiner/test-results', [
            'student_id' => $student->id,
            'test_id' => $test->id,
            'tested_surah' => 'الفاتحة',
            'memorization_score' => 80,
            'tajweed_score' => 80,
            'review_score' => 80,
            'client_recorded_at' => '2026-08-12 11:30:00',
        ])->assertCreated();

        $result = TestResult::query()->firstOrFail();
        $this->assertSame('2026-08-12 11:30:00', $result->tested_at->toDateTimeString());
        $this->assertSame('2026-08-12 20:00:00', $result->synced_at->toDateTimeString());
    }

    public function test_replaying_an_identical_result_does_not_renotify_the_teacher(): void
    {
        Carbon::setTestNow('2026-08-12 20:00:00');
        [, $student, $test, $teacher, $token] = $this->seedExaminerScope();

        $payload = [
            'student_id' => $student->id,
            'test_id' => $test->id,
            'tested_surah' => 'الفاتحة',
            'memorization_score' => 80,
            'tajweed_score' => 80,
            'review_score' => 80,
            'client_recorded_at' => '2026-08-12 11:30:00',
        ];

        $this->withToken($token)->postJson('/api/examiner/test-results', $payload)->assertCreated();
        $this->withToken($token)->postJson('/api/examiner/test-results', $payload)->assertCreated();

        $this->assertSame(1, TestResult::query()->count());
        $this->assertSame(1, AppNotification::query()->where('user_id', $teacher->id)->count());

        // أما تصحيح الدرجة فعلاً فيستحق إشعاراً جديداً.
        $this->withToken($token)->postJson('/api/examiner/test-results', array_merge($payload, [
            'memorization_score' => 95,
        ]))->assertCreated();

        $this->assertSame(2, AppNotification::query()->where('user_id', $teacher->id)->count());
    }
}
