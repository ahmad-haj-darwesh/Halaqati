<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Halaqah;
use App\Models\SupervisionRubric;
use App\Models\SupervisionRubricItem;
use App\Models\SupervisoryVisit;
use App\Models\SupervisoryVisitScore;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupervisionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $admin->syncRoles(['Admin']);

        // supervisor (EducationalSupervisor) scoped via managedCenters (admin_user_id on center)
        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@gmail.com'],
            ['name' => 'Educational Supervisor', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $supervisor->syncRoles(['EducationalSupervisor']);

        $center = Center::query()->first();
        if (! $center) {
            return;
        }

        // Attach scope (simplified): make supervisor "own" this center for managedCenters()
        $center->update(['admin_user_id' => $supervisor->id]);

        $halaqah = Halaqah::where('center_id', $center->id)->first();
        $teacherProfile = TeacherProfile::where('halaqah_id', $halaqah?->id)->first();

        if (! $halaqah || ! $teacherProfile) {
            return;
        }

        $rubric = SupervisionRubric::firstOrCreate(
            ['name' => 'نموذج تقييم الزيارة - v1'],
            [
                'description' => 'قالب افتراضي للزيارات',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ]
        );

        $items = [
            ['key' => 'plan_commitment', 'label' => 'الالتزام بالخطة'],
            ['key' => 'explanation_skill', 'label' => 'مهارة الشرح'],
            ['key' => 'halaqah_management', 'label' => 'إدارة الحلقة'],
            ['key' => 'tajweed', 'label' => 'التجويد'],
            ['key' => 'engagement', 'label' => 'التفاعل'],
            ['key' => 'discipline', 'label' => 'الانضباط'],
            ['key' => 'attendance_followup', 'label' => 'متابعة الحضور'],
            ['key' => 'student_motivation', 'label' => 'تحفيز الطلاب'],
        ];

        foreach ($items as $i => $item) {
            SupervisionRubricItem::updateOrCreate(
                ['supervision_rubric_id' => $rubric->id, 'key' => $item['key']],
                [
                    'label' => $item['label'],
                    'max_score' => 5,
                    'sort_order' => $i * 10,
                    'is_active' => true,
                ]
            );
        }

        $visit = SupervisoryVisit::create([
            'supervision_rubric_id' => $rubric->id,
            'supervisor_user_id' => $supervisor->id,
            'center_id' => $center->id,
            'halaqah_id' => $halaqah->id,
            'teacher_user_id' => $teacherProfile->user_id,
            'visited_at' => now()->subDays(3),
            'duration_minutes' => 60,
            'overall_level' => 'good',
            'summary' => 'زيارة تجريبية',
            'recommendations' => 'التركيز على التجويد والمراجعة',
            'is_finalized' => true,
        ]);

        $rubricItems = $rubric->items()->where('is_active', true)->get();
        foreach ($rubricItems as $ri) {
            SupervisoryVisitScore::create([
                'supervisory_visit_id' => $visit->id,
                'supervision_rubric_item_id' => $ri->id,
                'score' => 4,
                'note' => null,
            ]);
        }

        $visit->recomputeOverallScore();
        $visit->save();
    }
}

