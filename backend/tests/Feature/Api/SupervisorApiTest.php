<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\SupervisorFieldVisit;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** @return array{0: User, 1: Center, 2: Halaqah, 3: User, 4: Student} */
    private function seedSupervisorScope(): array
    {
        $region = Region::factory()->create();
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('CenterSupervisor');

        $center = Center::factory()->create([
            'region_id' => $region->id,
            'admin_user_id' => $supervisor->id,
        ]);

        $halaqah = Halaqah::factory()->create(['center_id' => $center->id]);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole('Teacher');
        TeacherProfile::create([
            'user_id' => $teacher->id,
            'halaqah_id' => $halaqah->id,
            'phone' => '0500000000',
        ]);

        $student = Student::factory()->create();
        Enrollment::create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        return [$supervisor, $center, $halaqah, $teacher, $student];
    }

    public function test_supervisor_can_fetch_stats(): void
    {
        [$supervisor, $center, $halaqah, $teacher, $student] = $this->seedSupervisorScope();
        $token = $supervisor->createToken('mobile-app')->plainTextToken;

        SupervisorFieldVisit::create([
            'supervisor_user_id' => $supervisor->id,
            'teacher_user_id' => $teacher->id,
            'center_id' => $center->id,
            'visit_date' => now()->toDateString(),
            'teaching_skill_score' => 8,
            'plan_adherence_score' => 7,
            'student_engagement_score' => 9,
            'notes' => null,
            'recommendations' => null,
            'status' => 'completed',
        ]);

        AttendanceRecord::create([
            'halaqah_id' => $halaqah->id,
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'status' => AttendanceRecord::STATUS_PRESENT,
            'recorded_by_user_id' => $teacher->id,
            'notes' => null,
        ]);

        $res = $this->withToken($token)->getJson('/api/supervisor/stats');
        $res->assertOk();
        $res->assertJsonPath('visits_this_month', 1);
        $res->assertJsonPath('centers_count', 1);
        $res->assertJsonStructure([
            'visits_this_month',
            'avg_teaching_score',
            'avg_plan_score',
            'avg_engagement',
            'unvisited_halaqahs',
            'attendance_rate_7d',
            'centers_count',
        ]);
    }

    public function test_supervisor_can_fetch_teacher_detail_within_scope(): void
    {
        [$supervisor, , , $teacher] = $this->seedSupervisorScope();
        $token = $supervisor->createToken('mobile-app')->plainTextToken;

        $res = $this->withToken($token)->getJson("/api/supervisor/teachers/{$teacher->id}");
        $res->assertOk();
        $res->assertJsonPath('id', $teacher->id);
        $res->assertJsonPath('name', $teacher->name);
    }

    public function test_supervisor_cannot_fetch_teacher_outside_scope(): void
    {
        [$supervisor] = $this->seedSupervisorScope();
        $token = $supervisor->createToken('mobile-app')->plainTextToken;

        $otherRegion = Region::factory()->create();
        $otherAdmin = User::factory()->create(['is_active' => true]);
        $otherAdmin->assignRole('CenterSupervisor');
        $otherCenter = Center::factory()->create([
            'region_id' => $otherRegion->id,
            'admin_user_id' => $otherAdmin->id,
        ]);
        $otherHalaqah = Halaqah::factory()->create(['center_id' => $otherCenter->id]);
        $otherTeacher = User::factory()->create(['is_active' => true]);
        $otherTeacher->assignRole('Teacher');
        TeacherProfile::create([
            'user_id' => $otherTeacher->id,
            'halaqah_id' => $otherHalaqah->id,
            'phone' => '0511111111',
        ]);

        $res = $this->withToken($token)->getJson("/api/supervisor/teachers/{$otherTeacher->id}");
        $res->assertForbidden();
    }

    public function test_supervisor_can_fetch_halaqah_daily_records(): void
    {
        [$supervisor, , $halaqah, $teacher] = $this->seedSupervisorScope();
        $token = $supervisor->createToken('mobile-app')->plainTextToken;
        $date = now()->toDateString();

        $res = $this->withToken($token)->getJson("/api/supervisor/halaqahs/{$halaqah->id}/daily?date={$date}");
        $res->assertOk();
        $res->assertJsonPath('halaqah_name', $halaqah->name);
        $res->assertJsonStructure([
            'summary' => ['total', 'present', 'absent_excused', 'absent_unexcused', 'not_recorded', 'attendance_rate'],
            'records',
        ]);
    }

    public function test_supervisor_cannot_fetch_halaqah_outside_scope(): void
    {
        [$supervisor] = $this->seedSupervisorScope();
        $token = $supervisor->createToken('mobile-app')->plainTextToken;

        $otherAdmin = User::factory()->create(['is_active' => true]);
        $otherAdmin->assignRole('CenterSupervisor');
        $otherCenter = Center::factory()->create([
            'region_id' => Region::factory()->create()->id,
            'admin_user_id' => $otherAdmin->id,
        ]);
        $otherHalaqah = Halaqah::factory()->create(['center_id' => $otherCenter->id]);

        $res = $this->withToken($token)->getJson("/api/supervisor/halaqahs/{$otherHalaqah->id}/daily");
        $res->assertForbidden();
    }

    public function test_supervisor_can_store_a_visit(): void
    {
        [$supervisor, $center, , $teacher] = $this->seedSupervisorScope();
        $token = $supervisor->createToken('mobile-app')->plainTextToken;

        $payload = [
            'teacher_id' => $teacher->id,
            'center_id' => $center->id,
            'visit_date' => now()->toDateString(),
            'teaching_skill_score' => 8,
            'plan_adherence_score' => 7,
            'student_engagement_score' => 9,
            'notes' => 'ملاحظة',
            'recommendations' => null,
        ];

        $res = $this->withToken($token)->postJson('/api/supervisor/visits', $payload);
        $res->assertCreated();
        $res->assertJsonPath('visit_id', SupervisorFieldVisit::first()->id);
        $this->assertDatabaseHas('supervisor_field_visits', [
            'supervisor_user_id' => $supervisor->id,
            'teacher_user_id' => $teacher->id,
            'center_id' => $center->id,
        ]);
    }

    public function test_supervisor_cannot_store_visit_for_center_outside_scope(): void
    {
        [$supervisor, , , $teacher] = $this->seedSupervisorScope();
        $token = $supervisor->createToken('mobile-app')->plainTextToken;

        $foreignAdmin = User::factory()->create(['is_active' => true]);
        $foreignAdmin->assignRole('CenterSupervisor');
        $foreignCenter = Center::factory()->create([
            'region_id' => Region::factory()->create()->id,
            'admin_user_id' => $foreignAdmin->id,
        ]);

        $payload = [
            'teacher_id' => $teacher->id,
            'center_id' => $foreignCenter->id,
            'visit_date' => now()->toDateString(),
            'teaching_skill_score' => 5,
            'plan_adherence_score' => 5,
            'student_engagement_score' => 5,
        ];

        $res = $this->withToken($token)->postJson('/api/supervisor/visits', $payload);
        $res->assertForbidden();
    }

    public function test_supervisor_cannot_store_visit_for_teacher_not_in_that_center(): void
    {
        $region = Region::factory()->create();
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('CenterSupervisor');

        $centerA = Center::factory()->create([
            'region_id' => $region->id,
            'admin_user_id' => $supervisor->id,
        ]);
        $centerB = Center::factory()->create([
            'region_id' => $region->id,
            'admin_user_id' => $supervisor->id,
        ]);

        $halaqahA = Halaqah::factory()->create(['center_id' => $centerA->id]);
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole('Teacher');
        TeacherProfile::create([
            'user_id' => $teacher->id,
            'halaqah_id' => $halaqahA->id,
            'phone' => '050',
        ]);

        $token = $supervisor->createToken('mobile-app')->plainTextToken;

        $payload = [
            'teacher_id' => $teacher->id,
            'center_id' => $centerB->id,
            'visit_date' => now()->toDateString(),
            'teaching_skill_score' => 6,
            'plan_adherence_score' => 6,
            'student_engagement_score' => 6,
        ];

        $res = $this->withToken($token)->postJson('/api/supervisor/visits', $payload);
        $res->assertUnprocessable();
        $res->assertJsonValidationErrors(['teacher_id']);
    }

    public function test_supervisor_can_fetch_own_visits_history(): void
    {
        [$supervisor, $center, , $teacher] = $this->seedSupervisorScope();
        $token = $supervisor->createToken('mobile-app')->plainTextToken;

        SupervisorFieldVisit::create([
            'supervisor_user_id' => $supervisor->id,
            'teacher_user_id' => $teacher->id,
            'center_id' => $center->id,
            'visit_date' => now()->subDay()->toDateString(),
            'teaching_skill_score' => 7,
            'plan_adherence_score' => 7,
            'student_engagement_score' => 7,
            'notes' => 'n',
            'recommendations' => null,
            'status' => 'completed',
        ]);

        $res = $this->withToken($token)->getJson('/api/supervisor/visits');
        $res->assertOk();
        $res->assertJsonPath('total', 1);
        $res->assertJsonCount(1, 'data');
        $res->assertJsonPath('data.0.teacher_name', $teacher->name);
    }

    public function test_supervisor_can_fetch_attendance_stats(): void
    {
        [$supervisor, , $halaqah, $teacher] = $this->seedSupervisorScope();
        $token = $supervisor->createToken('mobile-app')->plainTextToken;

        $student = Student::factory()->create();
        Enrollment::create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        AttendanceRecord::create([
            'halaqah_id' => $halaqah->id,
            'student_id' => $student->id,
            'date' => now()->subDay()->toDateString(),
            'status' => AttendanceRecord::STATUS_PRESENT,
            'recorded_by_user_id' => $teacher->id,
            'notes' => null,
        ]);

        $res = $this->withToken($token)->getJson('/api/supervisor/attendance-stats?days=7');
        $res->assertOk();
        $res->assertJsonPath('period_days', 7);
        $res->assertJsonStructure(['halaqahs', 'overall_rate']);

        $rates = collect($res->json('halaqahs'))->pluck('attendance_rate')->all();
        for ($i = 1, $c = count($rates); $i < $c; $i++) {
            $this->assertGreaterThanOrEqual($rates[$i - 1], $rates[$i], 'halaqahs should be sorted by attendance_rate descending');
        }
    }
}
