<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\SupervisionRubric;
use App\Models\SupervisionRubricItem;
use App\Models\SupervisoryVisit;
use App\Models\SupervisoryVisitScore;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5SupervisionDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unique_visit_id_and_rubric_item_id_prevents_duplicate_axis_score(): void
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
        $item = SupervisionRubricItem::factory()->create(['supervision_rubric_id' => $rubric->id, 'key' => 'x']);

        $visit = SupervisoryVisit::create([
            'supervision_rubric_id' => $rubric->id,
            'supervisor_user_id' => $supervisor->id,
            'center_id' => $center->id,
            'halaqah_id' => $halaqah->id,
            'teacher_user_id' => $teacher->id,
            'visited_at' => now(),
        ]);

        SupervisoryVisitScore::create([
            'supervisory_visit_id' => $visit->id,
            'supervision_rubric_item_id' => $item->id,
            'score' => 3,
        ]);

        $this->expectException(QueryException::class);
        SupervisoryVisitScore::create([
            'supervisory_visit_id' => $visit->id,
            'supervision_rubric_item_id' => $item->id,
            'score' => 4,
        ]);
    }
}
