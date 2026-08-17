<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SuperAdminSeeder::class,
            EvaluationReasonsSeeder::class,
            SupervisionSeeder::class,
        ]);

        // Sample data for local development (Phase 2)
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $admin->syncRoles(['Admin']);

        $teacher = User::firstOrCreate(
            ['email' => 'teacher@gmail.com'],
            ['name' => 'Teacher', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $teacher->syncRoles(['Teacher']);

        $region = Region::firstOrCreate(['name' => 'منطقة تجريبية'], ['description' => '']);
        $center = Center::firstOrCreate(
            ['name' => 'مركز تجريبي'],
            ['region_id' => $region->id, 'admin_user_id' => $admin->id]
        );
        $halaqah = Halaqah::firstOrCreate(
            ['name' => 'حلقة تجريبية', 'center_id' => $center->id],
            ['description' => '', 'capacity' => 20]
        );

        TeacherProfile::firstOrCreate(
            ['user_id' => $teacher->id],
            ['halaqah_id' => $halaqah->id, 'phone' => null]
        );

        $students = Student::factory()->count(10)->create();
        foreach ($students as $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'halaqah_id' => $halaqah->id,
                'enrolled_at' => now()->toDateString(),
                'status' => Enrollment::STATUS_ACTIVE,
            ]);
        }
    }
}
