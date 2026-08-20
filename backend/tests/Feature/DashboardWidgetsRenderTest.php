<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\SupervisionAvgScoreByTeacherWidget;
use App\Filament\Widgets\TopAbsentTeachersWidget;
use App\Models\SupervisoryVisit;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\EvaluationReasonsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * اختبارات عرض ويدجت لوحة المؤشرات.
 *
 * Arabic: الويدجت تُعرَض عبر Livewire ولا تمرّ باختبارات الوحدة، فانكسارها لا يظهر
 * إلا على المتصفح كخطأ 500. هذه الاختبارات تعرضها فعلياً ببيانات حقيقية.
 *
 * الدافع: ويدجت متوسط درجات الزيارات كان يُسقط اللوحة كلها لأن استعلامه المُجمَّع
 * يعيد صفوفاً بلا مفتاح أساسي، و`getTableRecordKey` يشترط نصاً لا null.
 *
 * EN: Renders dashboard widgets with real data; aggregated queries lack a primary key.
 */
class DashboardWidgetsRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);
        $this->seed(EvaluationReasonsSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->actingAs(User::role('SuperAdmin')->firstOrFail());
    }

    public function test_supervision_average_widget_renders_with_aggregated_rows(): void
    {
        // الفلاتر الافتراضية تغطّي الشهر الحالي؛ نضمن وجود زيارة داخل النطاق.
        SupervisoryVisit::query()->update(['visited_at' => now()]);

        $this->assertGreaterThan(
            0,
            SupervisoryVisit::whereNotNull('overall_score')->count(),
            'الاختبار بلا معنى دون زيارات لها درجات',
        );

        Livewire::test(SupervisionAvgScoreByTeacherWidget::class)
            ->assertOk();
    }

    public function test_top_absent_teachers_widget_renders(): void
    {
        Livewire::test(TopAbsentTeachersWidget::class)
            ->assertOk();
    }

    public function test_supervision_average_widget_renders_when_empty(): void
    {
        SupervisoryVisit::query()->delete();

        Livewire::test(SupervisionAvgScoreByTeacherWidget::class)
            ->assertOk();
    }
}
