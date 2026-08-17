<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2TeacherStudentsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_teacher_students_endpoint_returns_only_students_in_teacher_halaqah(): void
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

        $res = $this->withToken($token)->getJson('/api/teacher/students');
        $res->assertOk();
        $res->assertJsonCount(1, 'data');
        $this->assertEquals('A', $res->json('data.0.full_name'));
    }

    public function test_teacher_cannot_access_student_outside_halaqah(): void
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

        $this->withToken($token)->getJson("/api/teacher/students/{$student->id}")->assertForbidden();
    }
}
