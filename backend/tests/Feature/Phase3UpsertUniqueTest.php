<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\DailyEvaluation;
use App\Models\Enrollment;
use App\Models\EvaluationReason;
use App\Models\Halaqah;
use App\Models\MemorizationEntry;
use App\Models\Region;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\EvaluationReasonsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3UpsertUniqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(EvaluationReasonsSeeder::class);
    }

    public function test_upsert_creates_then_updates_without_duplicates(): void
    {
        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'H1', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $student = Student::factory()->create();
        Enrollment::create(['student_id' => $student->id, 'halaqah_id' => $halaqah->id, 'status' => Enrollment::STATUS_ACTIVE, 'enrolled_at' => now()->toDateString()]);

        $reason = EvaluationReason::query()->first();

        $token = $teacher->createToken('teacher-app')->plainTextToken;
        $date = now()->toDateString();

        $payload = [
            'date' => $date,
            'records' => [[
                'student_id' => $student->id,
                'attendance_status' => AttendanceRecord::STATUS_PRESENT,
                'attendance_note' => 'ok',
                'evaluation_overall' => DailyEvaluation::OVERALL_EXCELLENT,
                'reason_ids' => [$reason->id],
                'general_note' => 'good',
                'memorization_from' => 'A',
                'memorization_to' => 'B',
                'revision_from' => 'C',
                'revision_to' => 'D',
                'mistakes' => 'none',
            ]],
        ];

        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', $payload)->assertOk();

        $this->assertEquals(1, AttendanceRecord::count());
        $this->assertEquals(1, DailyEvaluation::count());
        $this->assertEquals(1, MemorizationEntry::count());

        $payload['records'][0]['attendance_note'] = 'updated';
        $payload['records'][0]['mistakes'] = 'm1';
        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', $payload)->assertOk();

        $this->assertEquals(1, AttendanceRecord::count());
        $this->assertEquals('updated', AttendanceRecord::first()->notes);
        $this->assertEquals(1, MemorizationEntry::count());
        $this->assertEquals('m1', MemorizationEntry::first()->mistakes);
    }

    public function test_teacher_cannot_upsert_for_student_outside_halaqah(): void
    {
        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah1 = Halaqah::create(['name' => 'H1', 'center_id' => $center->id]);
        $halaqah2 = Halaqah::create(['name' => 'H2', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah1->id]);

        $student = Student::factory()->create();
        Enrollment::create(['student_id' => $student->id, 'halaqah_id' => $halaqah2->id, 'status' => Enrollment::STATUS_ACTIVE, 'enrolled_at' => now()->toDateString()]);

        $token = $teacher->createToken('teacher-app')->plainTextToken;

        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => now()->toDateString(),
            'records' => [[
                'student_id' => $student->id,
                'attendance_status' => AttendanceRecord::STATUS_PRESENT,
            ]],
        ])->assertForbidden();
    }

    /**
     * حدود التاريخ بعد دعم العمل دون إنترنت.
     *
     * Arabic: كان الخادم يقبل اليوم الحالي فقط، فكان عمل معلّم سجّل بلا شبكة يضيع عند
     * تأخر المزامنة. صار يقبل أي يوم ضمن `offline.sync_window_days` ويرفض ما قبله —
     * وتفاصيل النافذة نفسها مغطّاة في `OfflineSyncTest`.
     * EN: Post-offline-support date bounds: in-window days accepted, older rejected.
     */
    public function test_teacher_can_upsert_within_the_sync_window_but_not_before_it(): void
    {
        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'H1', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $student = Student::factory()->create();
        Enrollment::create(['student_id' => $student->id, 'halaqah_id' => $halaqah->id, 'status' => Enrollment::STATUS_ACTIVE, 'enrolled_at' => now()->toDateString()]);

        $token = $teacher->createToken('teacher-app')->plainTextToken;

        $record = [[
            'student_id' => $student->id,
            'attendance_status' => AttendanceRecord::STATUS_PRESENT,
        ]];

        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => now()->subDay()->toDateString(),
            'records' => $record,
        ])->assertOk();

        $windowDays = (int) config('offline.sync_window_days');

        $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => now()->subDays($windowDays + 1)->toDateString(),
            'records' => $record,
        ])->assertStatus(422)->assertJsonValidationErrors(['date']);
    }
}
