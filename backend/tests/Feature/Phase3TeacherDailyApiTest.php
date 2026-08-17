<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\DailyEvaluation;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\EvaluationReasonsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Phase3TeacherDailyApiTest extends TestCase
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

    public function test_teacher_today_endpoint_only_lists_active_students_in_teacher_halaqah(): void
    {
        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah1 = Halaqah::create(['name' => 'H1', 'center_id' => $center->id]);
        $halaqah2 = Halaqah::create(['name' => 'H2', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah1->id]);

        $s1 = Student::factory()->create(['full_name' => 'A']);
        Enrollment::create(['student_id' => $s1->id, 'halaqah_id' => $halaqah1->id, 'status' => Enrollment::STATUS_ACTIVE, 'enrolled_at' => now()->toDateString()]);

        $s2 = Student::factory()->create(['full_name' => 'B']);
        Enrollment::create(['student_id' => $s2->id, 'halaqah_id' => $halaqah2->id, 'status' => Enrollment::STATUS_ACTIVE, 'enrolled_at' => now()->toDateString()]);

        $token = $teacher->createToken('teacher-app')->plainTextToken;

        $res = $this->withToken($token)->getJson('/api/teacher/halaqah/today');
        $res->assertOk();
        $res->assertJsonCount(1, 'students');
        $this->assertEquals('A', $res->json('students.0.full_name'));
        $this->assertNotEmpty($res->json('reasons'));
    }

    public function test_teacher_cannot_change_evaluation_after_one_hour_but_same_payload_ok(): void
    {
        Carbon::setTestNow('2026-04-04 10:00:00');

        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'H1', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $student = Student::factory()->create(['full_name' => 'EvalStudent']);
        Enrollment::create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => '2026-04-01',
        ]);

        $token = $teacher->createToken('teacher-app')->plainTextToken;
        $date = '2026-04-04';

        $res = $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => $date,
            'records' => [
                [
                    'student_id' => $student->id,
                    'evaluation_overall' => DailyEvaluation::OVERALL_GOOD,
                ],
            ],
        ]);
        $res->assertOk();

        Carbon::setTestNow('2026-04-04 11:01:00');

        $blocked = $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => $date,
            'records' => [
                [
                    'student_id' => $student->id,
                    'evaluation_overall' => DailyEvaluation::OVERALL_EXCELLENT,
                ],
            ],
        ]);
        $blocked->assertStatus(422);

        $same = $this->withToken($token)->postJson('/api/teacher/daily-records/upsert', [
            'date' => $date,
            'records' => [
                [
                    'student_id' => $student->id,
                    'evaluation_overall' => DailyEvaluation::OVERALL_GOOD,
                ],
            ],
        ]);
        $same->assertOk();

        $today = $this->withToken($token)->getJson('/api/teacher/halaqah/today?date='.$date);
        $today->assertOk();
        $this->assertFalse($today->json('students.0.evaluation.can_edit'));
    }
}
