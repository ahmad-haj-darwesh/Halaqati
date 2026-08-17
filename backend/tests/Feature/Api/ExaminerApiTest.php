<?php

namespace Tests\Feature\Api;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Models\TestResult;
use App\Models\User;
use App\Support\ExaminerScore;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExaminerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** @return array{0: User, 1: Center, 2: Halaqah, 3: Student, 4: Test, 5: TestAssignment} */
    private function seedExaminerScope(): array
    {
        $region = Region::create(['name' => 'R1']);
        $examiner = User::factory()->create(['is_active' => true]);
        $examiner->assignRole('Examiner');

        $center = Center::create([
            'name' => 'C1',
            'region_id' => $region->id,
            'admin_user_id' => $examiner->id,
        ]);

        $halaqah = Halaqah::create(['name' => 'H1', 'center_id' => $center->id]);

        $creator = User::factory()->create();
        $test = Test::create([
            'type' => Test::TYPE_REGULAR,
            'title' => 'اختبار تجريبي',
            'description' => null,
            'scope_halaqah_id' => $halaqah->id,
            'scope_center_id' => null,
            'scope_region_id' => null,
            'scheduled_at' => now(),
            'created_by_user_id' => $creator->id,
            'is_published' => true,
            'sampling_strategy' => null,
            'sampling_count' => null,
            'sampling_percent' => null,
            'sampling_seed' => null,
            'sampling_active_only' => true,
        ]);

        $student = Student::factory()->create(['full_name' => 'طالب 1']);
        Enrollment::create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        $assignment = TestAssignment::create([
            'test_id' => $test->id,
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'assigned_at' => now(),
            'assigned_by_user_id' => $creator->id,
            'status' => TestAssignment::STATUS_ASSIGNED,
        ]);

        return [$examiner, $center, $halaqah, $student, $test, $assignment];
    }

    public function test_examiner_can_fetch_stats(): void
    {
        [$examiner, , , $student, $test, $assignment] = $this->seedExaminerScope();
        $token = $examiner->createToken('mobile-app')->plainTextToken;

        $res = $this->withToken($token)->getJson('/api/examiner/stats');
        $res->assertOk();
        $res->assertJsonPath('total_assignments', 1);
        $res->assertJsonPath('completed', 0);
        $res->assertJsonPath('pending', 1);

        TestResult::create([
            'test_assignment_id' => $assignment->id,
            'examiner_user_id' => $examiner->id,
            'total_score' => 80,
            'level' => TestResult::LEVEL_GOOD,
            'notes' => null,
            'tested_at' => now(),
        ]);

        $res2 = $this->withToken($token)->getJson('/api/examiner/stats');
        $res2->assertJsonPath('completed', 1);
        $res2->assertJsonPath('pending', 0);
    }

    public function test_examiner_can_fetch_student_detail_within_scope(): void
    {
        [$examiner, , , $student] = $this->seedExaminerScope();
        $token = $examiner->createToken('mobile-app')->plainTextToken;

        $res = $this->withToken($token)->getJson("/api/examiner/students/{$student->id}");
        $res->assertOk();
        $res->assertJsonPath('name', 'طالب 1');
    }

    public function test_examiner_cannot_fetch_student_outside_scope(): void
    {
        [$examiner] = $this->seedExaminerScope();

        $region = Region::create(['name' => 'R2']);
        $otherCenter = Center::create(['name' => 'C2', 'region_id' => $region->id, 'admin_user_id' => null]);
        $otherHalaqah = Halaqah::create(['name' => 'H2', 'center_id' => $otherCenter->id]);
        $otherStudent = Student::factory()->create(['full_name' => 'خارج النطاق']);
        Enrollment::create([
            'student_id' => $otherStudent->id,
            'halaqah_id' => $otherHalaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        $token = $examiner->createToken('mobile-app')->plainTextToken;
        $res = $this->withToken($token)->getJson("/api/examiner/students/{$otherStudent->id}");
        $res->assertForbidden();
    }

    public function test_examiner_can_submit_test_result(): void
    {
        [$examiner, , , $student, $test, $assignment] = $this->seedExaminerScope();
        $token = $examiner->createToken('mobile-app')->plainTextToken;

        $payload = [
            'student_id' => $student->id,
            'test_id' => $test->id,
            'tested_surah' => 'الملك',
            'memorization_score' => 80,
            'tajweed_score' => 90,
            'review_score' => 70,
            'notes' => 'ملاحظة',
        ];

        $res = $this->withToken($token)->postJson('/api/examiner/test-results', $payload);
        $res->assertStatus(201);
        $expectedTotal = ExaminerScore::totalFromComponents(80, 90, 70);
        $res->assertJsonPath('total_score', $expectedTotal);
        $this->assertDatabaseCount('test_results', 1);
    }

    public function test_examiner_cannot_submit_result_for_student_outside_scope(): void
    {
        [$examiner] = $this->seedExaminerScope();

        $region = Region::create(['name' => 'R3']);
        $otherCenter = Center::create(['name' => 'C3', 'region_id' => $region->id, 'admin_user_id' => null]);
        $otherHalaqah = Halaqah::create(['name' => 'H3', 'center_id' => $otherCenter->id]);
        $otherStudent = Student::factory()->create();
        Enrollment::create([
            'student_id' => $otherStudent->id,
            'halaqah_id' => $otherHalaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        $creator = User::factory()->create();
        $otherTest = Test::create([
            'type' => Test::TYPE_REGULAR,
            'title' => 'آخر',
            'description' => null,
            'scope_halaqah_id' => $otherHalaqah->id,
            'scope_center_id' => null,
            'scope_region_id' => null,
            'scheduled_at' => now(),
            'created_by_user_id' => $creator->id,
            'is_published' => true,
            'sampling_strategy' => null,
            'sampling_count' => null,
            'sampling_percent' => null,
            'sampling_seed' => null,
            'sampling_active_only' => true,
        ]);

        TestAssignment::create([
            'test_id' => $otherTest->id,
            'student_id' => $otherStudent->id,
            'halaqah_id' => $otherHalaqah->id,
            'assigned_at' => now(),
            'assigned_by_user_id' => $creator->id,
            'status' => TestAssignment::STATUS_ASSIGNED,
        ]);

        $token = $examiner->createToken('mobile-app')->plainTextToken;

        $res = $this->withToken($token)->postJson('/api/examiner/test-results', [
            'student_id' => $otherStudent->id,
            'test_id' => $otherTest->id,
            'tested_surah' => 'الفاتحة',
            'memorization_score' => 50,
            'tajweed_score' => 50,
            'review_score' => 50,
        ]);

        $res->assertForbidden();
    }

    public function test_total_score_formula_matches_weights(): void
    {
        $this->assertSame(81, ExaminerScore::totalFromComponents(80, 90, 70));
        $this->assertSame(50, ExaminerScore::totalFromComponents(100, 0, 0));
    }

    public function test_duplicate_result_updates_not_duplicates(): void
    {
        [$examiner, , , $student, $test, $assignment] = $this->seedExaminerScope();
        $token = $examiner->createToken('mobile-app')->plainTextToken;

        $base = [
            'student_id' => $student->id,
            'test_id' => $test->id,
            'tested_surah' => 'الملك',
            'memorization_score' => 60,
            'tajweed_score' => 60,
            'review_score' => 60,
        ];

        $r1 = $this->withToken($token)->postJson('/api/examiner/test-results', $base);
        $r1->assertStatus(201);
        $id1 = $r1->json('result_id');
        $r1->assertJsonPath('is_update', false);

        $r2 = $this->withToken($token)->postJson('/api/examiner/test-results', array_merge($base, [
            'memorization_score' => 90,
            'tajweed_score' => 90,
            'review_score' => 90,
        ]));
        $r2->assertStatus(201);
        $r2->assertJsonPath('result_id', $id1);
        $r2->assertJsonPath('is_update', true);

        $this->assertDatabaseCount('test_results', 1);
    }
}
