<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\StudentProfileSubmission;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\StudentProfileSubmissionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * اختبارات مسار مراجعة ملف الطالب.
 *
 * Arabic: تغطي القاعدة الأساسية — تعديلات المعلّم لا تُطبّق على سجل الطالب إلا بعد
 * اعتماد المشرف، والرفض يُهملها دون أن يترك أثراً على البيانات المعتمدة.
 * EN: Covers the deferred-edit contract: teacher edits never touch the student until approval.
 */
class StudentProfileSubmissionFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $region = Region::create(['name' => 'الرياض']);
        $center = Center::create(['region_id' => $region->id, 'name' => 'مركز النور']);
        $halaqah = Halaqah::create(['center_id' => $center->id, 'name' => 'حلقة الفرقان', 'capacity' => 25]);

        $this->teacher = User::factory()->create(['name' => 'خالد العتيبي']);
        $this->teacher->assignRole('Teacher');

        TeacherProfile::create([
            'user_id'    => $this->teacher->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $this->student = Student::create([
            'full_name'                => 'عبدالله الغامدي',
            'gender'                   => 'male',
            'guardian_name'            => 'محمد الغامدي',
            'guardian_phone'           => '0551002233',
            'is_active'                => true,
            'teacher_may_edit_profile' => true,
        ]);

        Enrollment::create([
            'student_id'  => $this->student->id,
            'halaqah_id'  => $halaqah->id,
            'status'      => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->subMonth()->toDateString(),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name'      => 'عبدالله محمد الغامدي',
            'gender'         => 'male',
            'guardian_name'  => 'محمد الغامدي',
            'guardian_phone' => '0559998877',
        ], $overrides);
    }

    public function test_teacher_edit_does_not_touch_student_record(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->putJson("/api/teacher/students/{$this->student->id}/profile", $this->payload())
            ->assertOk()
            ->assertJsonPath('message', 'draft_saved');

        $this->student->refresh();

        // سجل الطالب المعتمد لم يتغيّر
        $this->assertSame('عبدالله الغامدي', $this->student->full_name);
        $this->assertSame('0551002233', $this->student->guardian_phone);

        // التعديل محفوظ كمسودّة
        $draft = $this->student->draftProfileSubmission()->first();
        $this->assertNotNull($draft);
        $this->assertSame('عبدالله محمد الغامدي', $draft->full_name);
    }

    public function test_teacher_sees_own_draft_values_in_payload(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->putJson("/api/teacher/students/{$this->student->id}/profile", $this->payload());

        $this->getJson("/api/teacher/students/{$this->student->id}/profile")
            ->assertOk()
            ->assertJsonPath('student.full_name', 'عبدالله محمد الغامدي')
            ->assertJsonPath('student.has_unapproved_changes', true);
    }

    public function test_reject_leaves_student_untouched_and_discards_changes(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->putJson("/api/teacher/students/{$this->student->id}/profile", $this->payload());
        $this->postJson("/api/teacher/students/{$this->student->id}/profile/submit")->assertOk();

        $submission = $this->student->pendingProfileSubmission()->first();
        $this->assertNotNull($submission);

        $reviewer = User::factory()->create();
        $reviewer->assignRole('Admin');

        app(StudentProfileSubmissionService::class)
            ->reject($submission, $reviewer, 'الهوية غير مطابقة');

        $this->student->refresh();

        // جوهر الإصلاح: الرفض لا يترك أي أثر على بيانات الطالب
        $this->assertSame('عبدالله الغامدي', $this->student->full_name);
        $this->assertSame('0551002233', $this->student->guardian_phone);

        $submission->refresh();
        $this->assertSame(StudentProfileSubmission::STATUS_REJECTED, $submission->status);
        $this->assertSame('الهوية غير مطابقة', $submission->reviewer_note);

        // لم تعد هناك مسودّة ولا طلب معلّق
        $this->assertNull($this->student->draftProfileSubmission()->first());
        $this->assertNull($this->student->pendingProfileSubmission()->first());
    }

    public function test_approve_applies_changes_and_locks_profile(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->putJson("/api/teacher/students/{$this->student->id}/profile", $this->payload());
        $this->postJson("/api/teacher/students/{$this->student->id}/profile/submit")->assertOk();

        $submission = $this->student->pendingProfileSubmission()->first();

        $reviewer = User::factory()->create();
        $reviewer->assignRole('Admin');

        app(StudentProfileSubmissionService::class)->approve($submission, $reviewer);

        $this->student->refresh();

        $this->assertSame('عبدالله محمد الغامدي', $this->student->full_name);
        $this->assertSame('0559998877', $this->student->guardian_phone);
        $this->assertTrue((bool) $this->student->profile_locked);
        $this->assertFalse((bool) $this->student->teacher_may_edit_profile);
    }

    public function test_submit_without_changes_is_rejected(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->postJson("/api/teacher/students/{$this->student->id}/profile/submit")
            ->assertStatus(422)
            ->assertJsonPath('message', 'لا توجد تعديلات لإرسالها');
    }

    public function test_teacher_can_revise_draft_before_submitting(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->putJson("/api/teacher/students/{$this->student->id}/profile", $this->payload());
        $this->putJson("/api/teacher/students/{$this->student->id}/profile", $this->payload([
            'full_name' => 'عبدالله بن محمد الغامدي',
        ]))->assertOk();

        // مسودّة واحدة فقط تُحدَّث، لا مسودّات متراكمة
        $this->assertSame(1, $this->student->profileSubmissions()
            ->where('status', StudentProfileSubmission::STATUS_DRAFT)
            ->count());

        $this->assertSame(
            'عبدالله بن محمد الغامدي',
            $this->student->draftProfileSubmission()->first()->full_name,
        );
    }

    public function test_draft_is_hidden_from_reviewers_until_submitted(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->putJson("/api/teacher/students/{$this->student->id}/profile", $this->payload());

        $visible = \App\Filament\Resources\StudentProfileSubmissionResource::getEloquentQuery()->count();

        $this->assertSame(0, $visible, 'المسودّة يجب ألا تظهر للمشرف قبل الإرسال');
    }
}
