<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\SupervisionRubric;
use App\Models\SupervisionRubricItem;
use App\Models\SupervisoryVisit;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5SupervisoryVisitPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_educational_supervisor_can_create_visit_within_managed_center(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('EducationalSupervisor');

        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id, 'admin_user_id' => $supervisor->id]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $rubric = SupervisionRubric::factory()->create(['created_by_user_id' => $supervisor->id]);

        $visit = SupervisoryVisit::create([
            'supervision_rubric_id' => $rubric->id,
            'supervisor_user_id' => $supervisor->id,
            'center_id' => $center->id,
            'halaqah_id' => $halaqah->id,
            'teacher_user_id' => $teacher->id,
            'visited_at' => now(),
        ]);

        $this->assertTrue($supervisor->can('view', $visit));
        $this->assertTrue($supervisor->can('update', $visit));
    }

    public function test_educational_supervisor_cannot_create_or_view_visit_outside_scope(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('EducationalSupervisor');

        $region = Region::create(['name' => 'R']);
        $centerOther = Center::create(['name' => 'C2', 'region_id' => $region->id, 'admin_user_id' => null]);
        $halaqahOther = Halaqah::create(['name' => 'H2', 'center_id' => $centerOther->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqahOther->id]);

        $rubric = SupervisionRubric::factory()->create(['created_by_user_id' => $supervisor->id]);

        $visit = SupervisoryVisit::create([
            'supervision_rubric_id' => $rubric->id,
            'supervisor_user_id' => $supervisor->id,
            'center_id' => $centerOther->id,
            'halaqah_id' => $halaqahOther->id,
            'teacher_user_id' => $teacher->id,
            'visited_at' => now(),
        ]);

        $this->assertFalse($supervisor->can('view', $visit));
        $this->assertFalse($supervisor->can('update', $visit));
    }

    public function test_educational_supervisor_cannot_update_finalized_visit(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('EducationalSupervisor');

        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id, 'admin_user_id' => $supervisor->id]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $rubric = SupervisionRubric::factory()->create(['created_by_user_id' => $supervisor->id]);

        $visit = SupervisoryVisit::create([
            'supervision_rubric_id' => $rubric->id,
            'supervisor_user_id' => $supervisor->id,
            'center_id' => $center->id,
            'halaqah_id' => $halaqah->id,
            'teacher_user_id' => $teacher->id,
            'visited_at' => now(),
            'is_finalized' => true,
        ]);

        $this->assertFalse($supervisor->can('update', $visit));
    }

    public function test_admin_sees_only_visits_in_managed_centers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $region = Region::create(['name' => 'R']);
        $centerIn = Center::create(['name' => 'C1', 'region_id' => $region->id, 'admin_user_id' => $admin->id]);
        $centerOut = Center::create(['name' => 'C2', 'region_id' => $region->id, 'admin_user_id' => null]);

        $halaqahIn = Halaqah::create(['name' => 'H1', 'center_id' => $centerIn->id]);
        $halaqahOut = Halaqah::create(['name' => 'H2', 'center_id' => $centerOut->id]);

        $teacher1 = User::factory()->create();
        $teacher1->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher1->id, 'halaqah_id' => $halaqahIn->id]);

        $teacher2 = User::factory()->create();
        $teacher2->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher2->id, 'halaqah_id' => $halaqahOut->id]);

        $rubric = SupervisionRubric::factory()->create(['created_by_user_id' => $admin->id]);

        $vIn = SupervisoryVisit::create([
            'supervision_rubric_id' => $rubric->id,
            'supervisor_user_id' => $admin->id,
            'center_id' => $centerIn->id,
            'halaqah_id' => $halaqahIn->id,
            'teacher_user_id' => $teacher1->id,
            'visited_at' => now(),
        ]);
        $vOut = SupervisoryVisit::create([
            'supervision_rubric_id' => $rubric->id,
            'supervisor_user_id' => $admin->id,
            'center_id' => $centerOut->id,
            'halaqah_id' => $halaqahOut->id,
            'teacher_user_id' => $teacher2->id,
            'visited_at' => now(),
        ]);

        $policy = app(\App\Policies\SupervisoryVisitPolicy::class);
        $ids = $policy->scopeQueryForUser($admin, SupervisoryVisit::query())->pluck('id')->all();

        $this->assertTrue(in_array($vIn->id, $ids, true));
        $this->assertFalse(in_array($vOut->id, $ids, true));
    }
}
