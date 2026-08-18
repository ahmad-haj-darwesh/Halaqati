<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\DailyEvaluation;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\MemorizationEntry;
use App\Models\Student;
use App\Models\SupervisoryVisit;
use App\Models\TeacherProfile;
use App\Models\TestResult;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\EvaluationReasonsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Database\Seeders\SupervisionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبارات بذرة البيانات التجريبية.
 *
 * Arabic: البذرة تُشغَّل على الإنتاج، فالأهم التحقق من أنها لا تضاعف البيانات عند
 * إعادة التشغيل ولا تحذف ما هو موجود مسبقاً.
 * EN: Verifies the demo seeder is idempotent and non-destructive.
 */
class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);
        $this->seed(EvaluationReasonsSeeder::class);
        $this->seed(SupervisionSeeder::class);
    }

    public function test_it_creates_a_complete_dataset(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(3, Center::count(), 'ثلاثة مراكز');
        $this->assertSame(6, Halaqah::count(), 'ست حلقات');
        $this->assertSame(6, TeacherProfile::count(), 'ستة معلّمين');
        $this->assertSame(60, Enrollment::count(), '١٠ طلاب لكل حلقة');
        $this->assertSame(60, Student::count());

        $this->assertGreaterThan(900, AttendanceRecord::count(), 'سجلات حضور كافية للتقارير');
        $this->assertGreaterThan(700, DailyEvaluation::count());
        $this->assertGreaterThan(700, MemorizationEntry::count());
        $this->assertGreaterThan(20, TestResult::count());
        $this->assertSame(6, SupervisoryVisit::count(), 'زيارة لكل حلقة');
    }

    public function test_running_twice_does_not_duplicate_anything(): void
    {
        $this->seed(DemoDataSeeder::class);

        $before = [
            'centers' => Center::count(),
            'halaqahs' => Halaqah::count(),
            'students' => Student::count(),
            'enrollments' => Enrollment::count(),
            'attendance' => AttendanceRecord::count(),
            'evaluations' => DailyEvaluation::count(),
            'results' => TestResult::count(),
            'visits' => SupervisoryVisit::count(),
        ];

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($before['centers'], Center::count());
        $this->assertSame($before['halaqahs'], Halaqah::count());
        $this->assertSame($before['students'], Student::count());
        $this->assertSame($before['enrollments'], Enrollment::count());
        $this->assertSame($before['attendance'], AttendanceRecord::count());
        $this->assertSame($before['evaluations'], DailyEvaluation::count());
        $this->assertSame($before['results'], TestResult::count());
        $this->assertSame($before['visits'], SupervisoryVisit::count());
    }

    public function test_it_does_not_touch_pre_existing_data(): void
    {
        $existing = User::factory()->create([
            'email' => 'someone@example.com',
            'name' => 'مستخدم قائم',
        ]);
        $existingStudent = Student::factory()->create(['full_name' => 'طالب قائم مسبقاً']);

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('users', ['id' => $existing->id, 'name' => 'مستخدم قائم']);
        $this->assertDatabaseHas('students', ['id' => $existingStudent->id, 'full_name' => 'طالب قائم مسبقاً']);
    }

    public function test_demo_accounts_can_authenticate(): void
    {
        $this->seed(DemoDataSeeder::class);

        foreach (['teacher1@halaqaty.de', 'examiner1@halaqaty.de', 'supervisor1@halaqaty.de'] as $email) {
            $response = $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'password',
            ]);

            $response->assertOk()
                ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
        }
    }

    public function test_attendance_is_never_recorded_on_fridays(): void
    {
        $this->seed(DemoDataSeeder::class);

        $fridays = AttendanceRecord::all()
            ->filter(fn (AttendanceRecord $r) => \Illuminate\Support\Carbon::parse($r->date)->isFriday());

        $this->assertCount(0, $fridays, 'الجمعة عطلة — لا سجلات حضور');
    }

    public function test_absent_students_have_no_evaluation_or_memorization(): void
    {
        $this->seed(DemoDataSeeder::class);

        $absent = AttendanceRecord::where('status', '!=', AttendanceRecord::STATUS_PRESENT)->first();

        $this->assertNotNull($absent, 'يفترض وجود غيابات في البيانات التجريبية');

        $this->assertDatabaseMissing('daily_evaluations', [
            'student_id' => $absent->student_id,
            'date' => $absent->date,
            'halaqah_id' => $absent->halaqah_id,
        ]);
    }
}
