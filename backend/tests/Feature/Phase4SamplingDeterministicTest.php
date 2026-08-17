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

class Phase4SamplingDeterministicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_sampling_with_same_seed_is_deterministic(): void
    {
        $examiner = User::factory()->create();
        $examiner->assignRole('Examiner');

        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id, 'admin_user_id' => $examiner->id]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        // Deterministic student ids
        $students = Student::factory()->count(10)->create();
        foreach ($students as $s) {
            Enrollment::create([
                'student_id' => $s->id,
                'halaqah_id' => $halaqah->id,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now()->toDateString(),
            ]);
        }

        $t1 = Test::create([
            'type' => Test::TYPE_SAMPLING,
            'title' => 'S1',
            'scope_halaqah_id' => $halaqah->id,
            'created_by_user_id' => $examiner->id,
            'sampling_count' => 4,
            'sampling_seed' => 'same-seed',
        ]);
        $t2 = Test::create([
            'type' => Test::TYPE_SAMPLING,
            'title' => 'S2',
            'scope_halaqah_id' => $halaqah->id,
            'created_by_user_id' => $examiner->id,
            'sampling_count' => 4,
            'sampling_seed' => 'same-seed',
        ]);

        $svc = app(SamplingAssignmentService::class);
        $r1 = $svc->generate($t1, $examiner, ['count' => 4, 'seed' => 'same-seed']);
        $r2 = $svc->generate($t2, $examiner, ['count' => 4, 'seed' => 'same-seed']);

        $this->assertEquals($r1['student_ids'], $r2['student_ids']);
    }
}
