<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Models\TestResult;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4UniquesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unique_test_id_student_id_on_assignments(): void
    {
        $examiner = User::factory()->create();
        $examiner->assignRole('Examiner');

        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id, 'admin_user_id' => $examiner->id]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        $student = Student::factory()->create();
        Enrollment::create(['student_id' => $student->id, 'halaqah_id' => $halaqah->id, 'status' => Enrollment::STATUS_ACTIVE, 'enrolled_at' => now()->toDateString()]);

        $test = Test::create([
            'type' => Test::TYPE_REGULAR,
            'title' => 'T',
            'scope_halaqah_id' => $halaqah->id,
            'created_by_user_id' => $examiner->id,
        ]);

        TestAssignment::create([
            'test_id' => $test->id,
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'assigned_by_user_id' => $examiner->id,
            'assigned_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        TestAssignment::create([
            'test_id' => $test->id,
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'assigned_by_user_id' => $examiner->id,
            'assigned_at' => now(),
        ]);
    }

    public function test_unique_result_per_assignment(): void
    {
        $examiner = User::factory()->create();
        $examiner->assignRole('Examiner');

        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id, 'admin_user_id' => $examiner->id]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        $student = Student::factory()->create();
        Enrollment::create(['student_id' => $student->id, 'halaqah_id' => $halaqah->id, 'status' => Enrollment::STATUS_ACTIVE, 'enrolled_at' => now()->toDateString()]);

        $test = Test::create([
            'type' => Test::TYPE_REGULAR,
            'title' => 'T',
            'scope_halaqah_id' => $halaqah->id,
            'created_by_user_id' => $examiner->id,
        ]);

        $assignment = TestAssignment::create([
            'test_id' => $test->id,
            'student_id' => $student->id,
            'halaqah_id' => $halaqah->id,
            'assigned_by_user_id' => $examiner->id,
            'assigned_at' => now(),
        ]);

        TestResult::create([
            'test_assignment_id' => $assignment->id,
            'examiner_user_id' => $examiner->id,
            'tested_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        TestResult::create([
            'test_assignment_id' => $assignment->id,
            'examiner_user_id' => $examiner->id,
            'tested_at' => now(),
        ]);
    }
}
