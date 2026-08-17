<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\User;
use App\Policies\CenterPolicy;
use App\Policies\HalaqahPolicy;
use App\Policies\UserPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScopePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_view_own_center(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $region = Region::create(['name' => 'منطقة الاختبار']);
        $center = Center::create([
            'name'          => 'مركز الاختبار',
            'region_id'     => $region->id,
            'admin_user_id' => $admin->id,
        ]);

        $policy = new CenterPolicy();
        $this->assertTrue($policy->view($admin, $center));
    }

    public function test_admin_cannot_view_other_admin_center(): void
    {
        $admin1 = User::factory()->create();
        $admin1->assignRole('Admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('Admin');

        $region = Region::create(['name' => 'منطقة']);
        $center = Center::create([
            'name'          => 'مركز لمشرف آخر',
            'region_id'     => $region->id,
            'admin_user_id' => $admin2->id,
        ]);

        $policy = new CenterPolicy();
        $this->assertFalse($policy->view($admin1, $center));
    }

    public function test_admin_cannot_update_halaqah_outside_scope(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('Admin');

        $region  = Region::create(['name' => 'منطقة']);
        $center  = Center::create(['name' => 'مركز آخر', 'region_id' => $region->id, 'admin_user_id' => $otherAdmin->id]);
        $halaqah = Halaqah::create(['name' => 'حلقة', 'center_id' => $center->id]);

        $policy = new HalaqahPolicy();
        $this->assertFalse($policy->update($admin, $halaqah));
    }

    public function test_admin_cannot_update_superadmin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $policy = new UserPolicy();
        $this->assertFalse($policy->update($admin, $superAdmin));
    }
}
