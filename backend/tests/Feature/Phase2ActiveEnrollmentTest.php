<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase2ActiveEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_student_cannot_have_two_active_enrollments(): void
    {
        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah1 = Halaqah::create(['name' => 'H1', 'center_id' => $center->id]);
        $halaqah2 = Halaqah::create(['name' => 'H2', 'center_id' => $center->id]);

        $student = Student::factory()->create();

        Enrollment::create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah1->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);

        $this->expectException(ValidationException::class);

        Enrollment::create([
            'student_id' => $student->id,
            'halaqah_id' => $halaqah2->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->toDateString(),
        ]);
    }
}
