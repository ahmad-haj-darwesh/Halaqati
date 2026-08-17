<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\Test;
use App\Models\User;
use App\Services\SamplingAssignmentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4ExaminerTestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_examiner_can_create_sampling_test_within_scope_and_generate_assignments(): void
    {
        $examiner = User::factory()->create();
        $examiner->assignRole('Examiner');

        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C1', 'region_id' => $region->id, 'admin_user_id' => $examiner->id]);
        $halaqah = Halaqah::create(['name' => 'H1', 'center_id' => $center->id]);

        $students = Student::factory()->count(5)->create();
        foreach ($students as $s) {
            Enrollment::create([
                'student_id' => $s->id,
                'halaqah_id' => $halaqah->id,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now()->toDateString(),
            ]);
        }

        $test = Test::create([
            'type' => Test::TYPE_SAMPLING,
            'title' => 'Sampling',
            'scope_center_id' => $center->id,
            'created_by_user_id' => $examiner->id,
            'sampling_strategy' => 'random',
            'sampling_count' => 3,
            'sampling_seed' => 'seed-1',
        ]);

        $this->assertTrue($examiner->can('update', $test));

        $service = app(SamplingAssignmentService::class);
        $res = $service->generate($test, $examiner, ['count' => 3, 'seed' => 'seed-1']);

        $this->assertEquals(3, $res['created']);
        $this->assertCount(3, $test->fresh()->assignments);
    }

    public function test_examiner_cannot_generate_sampling_outside_scope(): void
    {
        $examiner = User::factory()->create();
        $examiner->assignRole('Examiner');

        $region = Region::create(['name' => 'R']);
        $centerAllowed = Center::create(['name' => 'C1', 'region_id' => $region->id, 'admin_user_id' => $examiner->id]);
        $centerOther = Center::create(['name' => 'C2', 'region_id' => $region->id, 'admin_user_id' => null]);
        $halaqahOther = Halaqah::create(['name' => 'H2', 'center_id' => $centerOther->id]);

        $s = Student::factory()->create();
        Enrollment::create([
            'student_id' => $s->id,
            'halaqah_id' => $halaqahOther->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        $test = Test::create([
            'type' => Test::TYPE_SAMPLING,
            'title' => 'Sampling',
            'scope_center_id' => $centerOther->id,
            'created_by_user_id' => $examiner->id,
            'sampling_strategy' => 'random',
            'sampling_count' => 1,
            'sampling_seed' => 'x',
        ]);

        $this->assertFalse($examiner->can('update', $test));
    }
}
