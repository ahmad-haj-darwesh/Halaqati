<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\DailyEvaluation;
use App\Models\Enrollment;
use App\Models\EvaluationReason;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\EvaluationReasonsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(EvaluationReasonsSeeder::class);
    }

    public function test_monthly_report_returns_correct_counts_for_sample(): void
    {
        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'H1', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $student = Student::factory()->create();
        Enrollment::create(['student_id' => $student->id, 'halaqah_id' => $halaqah->id, 'status' => Enrollment::STATUS_ACTIVE, 'enrolled_at' => now()->toDateString()]);

        $month = '2026-04';
        $d1 = '2026-04-01';
        $d2 = '2026-04-02';

        AttendanceRecord::create([
            'halaqah_id' => $halaqah->id,
            'student_id' => $student->id,
            'date' => $d1,
            'status' => AttendanceRecord::STATUS_PRESENT,
            'recorded_by_user_id' => $teacher->id,
        ]);
        AttendanceRecord::create([
            'halaqah_id' => $halaqah->id,
            'student_id' => $student->id,
            'date' => $d2,
            'status' => AttendanceRecord::STATUS_UNEXCUSED,
            'recorded_by_user_id' => $teacher->id,
        ]);

        $reason = EvaluationReason::query()->where('type', EvaluationReason::TYPE_EXCELLENCE)->first();

        $eval1 = DailyEvaluation::create([
            'halaqah_id' => $halaqah->id,
            'student_id' => $student->id,
            'date' => $d1,
            'overall' => DailyEvaluation::OVERALL_EXCELLENT,
            'recorded_by_user_id' => $teacher->id,
        ]);
        $eval1->reasons()->sync([$reason->id]);

        DailyEvaluation::create([
            'halaqah_id' => $halaqah->id,
            'student_id' => $student->id,
            'date' => $d2,
            'overall' => DailyEvaluation::OVERALL_NEEDS_IMPROVEMENT,
            'recorded_by_user_id' => $teacher->id,
        ]);

        $token = $teacher->createToken('teacher-app')->plainTextToken;
        $res = $this->withToken($token)->getJson("/api/teacher/reports/monthly?month={$month}");
        $res->assertOk();

        $this->assertEquals(1, $res->json('attendance.present'));
        $this->assertEquals(1, $res->json('attendance.unexcused_absence'));
        $this->assertEquals(1, $res->json('evaluations.excellent'));
        $this->assertEquals(1, $res->json('evaluations.needs_improvement'));

        $reasonRow = collect($res->json('reasons'))->firstWhere('id', $reason->id);
        $this->assertNotNull($reasonRow);
        $this->assertEquals(1, $reasonRow['total']);
    }
}
