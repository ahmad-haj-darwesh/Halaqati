<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2EnrollmentScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_cannot_create_enrollment_outside_managed_centers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $region = Region::create(['name' => 'R']);
        $center1 = Center::create(['name' => 'C1', 'region_id' => $region->id, 'admin_user_id' => $admin->id]);
        $center2 = Center::create(['name' => 'C2', 'region_id' => $region->id, 'admin_user_id' => null]);

        $halaqahInScope = Halaqah::create(['name' => 'H1', 'center_id' => $center1->id]);
        $halaqahOutScope = Halaqah::create(['name' => 'H2', 'center_id' => $center2->id]);

        $student = Student::factory()->create();
        Enrollment::create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqahInScope->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        $policy = app(\App\Policies\EnrollmentPolicy::class);
        $this->assertFalse($policy->createForStudent($admin, $student, $halaqahOutScope->id));
        $this->assertTrue($policy->createForStudent($admin, $student, $halaqahInScope->id));
    }

    public function test_admin_sees_only_students_in_scope_via_student_policy_scope(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $region = Region::create(['name' => 'R']);
        $center1 = Center::create(['name' => 'C1', 'region_id' => $region->id, 'admin_user_id' => $admin->id]);
        $center2 = Center::create(['name' => 'C2', 'region_id' => $region->id, 'admin_user_id' => null]);

        $halaqahInScope = Halaqah::create(['name' => 'H1', 'center_id' => $center1->id]);
        $halaqahOutScope = Halaqah::create(['name' => 'H2', 'center_id' => $center2->id]);

        $studentIn = Student::factory()->create(['full_name' => 'In']);
        Enrollment::create([
            'student_id' => $studentIn->id,
            'halaqah_id' => $halaqahInScope->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        $studentOut = Student::factory()->create(['full_name' => 'Out']);
        Enrollment::create([
            'student_id' => $studentOut->id,
            'halaqah_id' => $halaqahOutScope->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        $policy = app(\App\Policies\StudentPolicy::class);
        $query = $policy->scopeQueryForUser($admin, Student::query());

        $this->assertEquals(['In'], $query->orderBy('full_name')->pluck('full_name')->all());
    }
}
