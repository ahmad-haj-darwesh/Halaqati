<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبارات مصادقة API لتطبيق الموبايل.
 *
 * Arabic: تغطي سيناريوهات تسجيل الدخول الأساسية (مسموح/مرفوض/بيانات خاطئة/غير مفعل)
 * بالإضافة إلى التحقق من حماية بعض النقاط الطرفية.
 * EN: Feature tests for mobile auth endpoints and access control basics.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * يجب أن يستطيع المعلم تسجيل الدخول عبر `/api/login`.
     * EN: A teacher can log in via the API.
     */
    public function test_teacher_can_login_via_api(): void
    {
        $teacher = User::factory()->create([
            'email'    => 'teacher@test.com',
            'password' => bcrypt('password'),
        ]);
        $teacher->assignRole('Teacher');

        $response = $this->postJson('/api/login', [
            'email'    => 'teacher@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
    }

    /**
     * المستخدم الذي لا يملك دوراً مسموحاً للموبايل لا يستطيع تسجيل الدخول.
     * EN: Non-allowed roles cannot log in via the mobile API.
     */
    public function test_non_teacher_cannot_login_via_api(): void
    {
        $admin = User::factory()->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Admin');

        $response = $this->postJson('/api/login', [
            'email'    => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
    }

    /**
     * بيانات اعتماد غير صحيحة يجب أن تُرفض.
     * EN: Invalid credentials are rejected.
     */
    public function test_invalid_credentials_are_rejected(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'nobody@test.com',
            'password' => 'wrong',
        ]);

        $response->assertUnprocessable();
    }

    /**
     * الحساب غير المفعّل لا يمكنه تسجيل الدخول.
     * EN: Inactive accounts cannot log in.
     */
    public function test_inactive_teacher_cannot_login(): void
    {
        $teacher = User::factory()->create([
            'email'     => 'inactive@test.com',
            'password'  => bcrypt('password'),
            'is_active' => false,
        ]);
        $teacher->assignRole('Teacher');

        $response = $this->postJson('/api/login', [
            'email'    => 'inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
    }

    /**
     * التحقق من الوصول لنقطة `/api/teacher/me` عند توفر توكن.
     * EN: Authenticated users can access the me endpoint.
     */
    public function test_teacher_can_access_me_endpoint(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        $token = $teacher->createToken('teacher-app')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/teacher/me');

        $response->assertOk()->assertJsonStructure(['id', 'name', 'email']);
    }

    /**
     * المستخدم غير الموثّق لا يمكنه الوصول لنقطة `/api/teacher/me`.
     * EN: Unauthenticated access is denied.
     */
    public function test_unauthenticated_cannot_access_me(): void
    {
        $this->getJson('/api/teacher/me')->assertUnauthorized();
    }
}
