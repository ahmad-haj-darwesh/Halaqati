<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\User;
use App\Services\DashboardKpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Phase6DashboardKpiScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_sees_all_centers_and_admin_is_scoped(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $super = User::factory()->create(['email' => 'superadmin@halqati.local']);
        $super->assignRole('SuperAdmin');

        $admin = User::factory()->create(['email' => 'admin@halqati.local']);
        $admin->assignRole('Admin');

        // keep dataset minimal (avoid seeded sample affecting counts)
        $region = Region::factory()->create();
        $centerA = Center::factory()->create(['region_id' => $region->id, 'admin_user_id' => $admin->id]);
        $centerB = Center::factory()->create(['region_id' => $region->id]); // not managed by admin

        $halaqahA = Halaqah::factory()->create(['center_id' => $centerA->id]);
        $halaqahB = Halaqah::factory()->create(['center_id' => $centerB->id]);

        $s1 = Student::factory()->create();
        $s2 = Student::factory()->create();

        Enrollment::factory()->create(['student_id' => $s1->id, 'halaqah_id' => $halaqahA->id, 'status' => Enrollment::STATUS_ACTIVE]);
        Enrollment::factory()->create(['student_id' => $s2->id, 'halaqah_id' => $halaqahB->id, 'status' => Enrollment::STATUS_ACTIVE]);

        $date = Carbon::today()->toDateString();
        AttendanceRecord::factory()->create(['halaqah_id' => $halaqahA->id, 'student_id' => $s1->id, 'date' => $date, 'status' => AttendanceRecord::STATUS_PRESENT]);
        AttendanceRecord::factory()->create(['halaqah_id' => $halaqahB->id, 'student_id' => $s2->id, 'date' => $date, 'status' => AttendanceRecord::STATUS_UNEXCUSED]);

        $service = app(DashboardKpiService::class);

        $kpiSuper = $service->kpisByScope('all', null, $date, $date, $super);
        $kpiAdmin = $service->kpisByScope('all', null, $date, $date, $admin);

        $this->assertSame(2, $kpiSuper['active_students']);
        $this->assertSame(1, $kpiAdmin['active_students']);

        $this->assertSame(1, $kpiSuper['attendance']['present']);
        $this->assertSame(1, $kpiSuper['attendance']['unexcused']);

        $this->assertSame(1, $kpiAdmin['attendance']['present']);
        $this->assertSame(0, $kpiAdmin['attendance']['unexcused']);
    }
}

