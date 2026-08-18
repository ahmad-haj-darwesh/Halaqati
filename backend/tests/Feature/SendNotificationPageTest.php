<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\SendNotification;
use App\Models\AppNotification;
use App\Models\Center;
use App\Models\Halaqah;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * اختبارات صفحة إرسال الإشعارات في لوحة الإدارة.
 *
 * Arabic: تغطي الصلاحية (من يصل للصفحة) والنطاق (مدير المركز لا يتجاوز مراكزه)
 * وأثر الإرسال الفعلي على جدول الإشعارات.
 * EN: Covers access control, center scoping, and the actual send effect.
 */
class SendNotificationPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeUser(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_can_access_the_page(): void
    {
        $this->actingAs($this->makeUser('SuperAdmin'));

        $this->assertTrue(SendNotification::canAccess());
    }

    public function test_teacher_cannot_access_the_page(): void
    {
        $this->actingAs($this->makeUser('Teacher'));

        $this->assertFalse(SendNotification::canAccess());
    }

    public function test_sending_to_a_role_stores_a_notification_for_each_member(): void
    {
        $this->actingAs($this->makeUser('SuperAdmin'));

        $teachers = collect(range(1, 3))->map(fn () => $this->makeUser('Teacher'));
        $examiner = $this->makeUser('Examiner');

        Livewire::test(SendNotification::class)
            ->set('data.target', 'role')
            ->set('data.role', 'Teacher')
            ->set('data.title', 'تذكير')
            ->set('data.body', 'يرجى تسجيل الحضور')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertSame(3, AppNotification::count());

        foreach ($teachers as $teacher) {
            $this->assertDatabaseHas('app_notifications', [
                'user_id' => $teacher->id,
                'title' => 'تذكير',
                'type' => 'admin_broadcast',
            ]);
        }

        $this->assertDatabaseMissing('app_notifications', ['user_id' => $examiner->id]);
    }

    public function test_sending_to_specific_users_targets_only_them(): void
    {
        $this->actingAs($this->makeUser('SuperAdmin'));

        $picked = $this->makeUser('Teacher');
        $other = $this->makeUser('Teacher');

        Livewire::test(SendNotification::class)
            ->set('data.target', 'users')
            ->set('data.user_ids', [$picked->id])
            ->set('data.title', 'رسالة خاصة')
            ->set('data.body', 'محتوى')
            ->call('send');

        $this->assertDatabaseHas('app_notifications', ['user_id' => $picked->id]);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $other->id]);
    }

    public function test_inactive_users_are_never_targeted(): void
    {
        $this->actingAs($this->makeUser('SuperAdmin'));

        $active = $this->makeUser('Teacher');
        $inactive = $this->makeUser('Teacher', ['is_active' => false]);

        Livewire::test(SendNotification::class)
            ->set('data.target', 'role')
            ->set('data.role', 'Teacher')
            ->set('data.title', 'عنوان')
            ->set('data.body', 'نص')
            ->call('send');

        $this->assertDatabaseHas('app_notifications', ['user_id' => $active->id]);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $inactive->id]);
    }

    public function test_center_admin_cannot_reach_teachers_outside_their_centers(): void
    {
        $admin = $this->makeUser('Admin');

        $myCenter = Center::factory()->create(['admin_user_id' => $admin->id]);
        $otherCenter = Center::factory()->create();

        $mine = $this->makeUser('Teacher');
        TeacherProfile::factory()->create([
            'user_id' => $mine->id,
            'halaqah_id' => Halaqah::factory()->create(['center_id' => $myCenter->id])->id,
        ]);

        $theirs = $this->makeUser('Teacher');
        TeacherProfile::factory()->create([
            'user_id' => $theirs->id,
            'halaqah_id' => Halaqah::factory()->create(['center_id' => $otherCenter->id])->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(SendNotification::class)
            ->set('data.target', 'role')
            ->set('data.role', 'Teacher')
            ->set('data.title', 'إعلان')
            ->set('data.body', 'خاص بمركزي')
            ->call('send');

        $this->assertDatabaseHas('app_notifications', ['user_id' => $mine->id]);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $theirs->id]);
    }

    public function test_empty_title_is_rejected(): void
    {
        $this->actingAs($this->makeUser('SuperAdmin'));
        $this->makeUser('Teacher');

        Livewire::test(SendNotification::class)
            ->set('data.target', 'role')
            ->set('data.role', 'Teacher')
            ->set('data.title', '')
            ->set('data.body', 'نص')
            ->call('send')
            ->assertHasErrors(['data.title']);

        $this->assertSame(0, AppNotification::count());
    }
}
