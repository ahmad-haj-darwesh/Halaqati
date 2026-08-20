<?php

namespace Tests\Feature;

use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->policy = new UserPolicy();
    }

    public function test_superadmin_can_view_any_users(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $this->assertTrue($this->policy->viewAny($superAdmin));
    }

    public function test_superadmin_can_update_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->assertTrue($this->policy->update($superAdmin, $admin));
    }

    public function test_superadmin_cannot_update_teacher(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $this->assertFalse($this->policy->update($superAdmin, $teacher));
    }

    public function test_superadmin_cannot_update_examiner(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $examiner = User::factory()->create();
        $examiner->assignRole('Examiner');

        $this->assertFalse($this->policy->update($superAdmin, $examiner));
    }

    public function test_superadmin_cannot_update_educational_supervisor(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $supervisor = User::factory()->create();
        $supervisor->assignRole('EducationalSupervisor');

        $this->assertFalse($this->policy->update($superAdmin, $supervisor));
    }

    public function test_superadmin_can_delete_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->assertTrue($this->policy->delete($superAdmin, $admin));
    }

    public function test_superadmin_cannot_delete_teacher(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $this->assertFalse($this->policy->delete($superAdmin, $teacher));
    }
public function test_superadmin_can_update_own_account(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $this->assertTrue($this->policy->update($superAdmin, $superAdmin));
    }

    public function test_superadmin_cannot_delete_own_account(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $this->assertFalse($this->policy->delete($superAdmin, $superAdmin));
    }

    public function test_superadmin_cannot_update_another_superadmin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $other = User::factory()->create();
        $other->assignRole('SuperAdmin');

        $this->assertFalse($this->policy->update($superAdmin, $other));
    }
}
