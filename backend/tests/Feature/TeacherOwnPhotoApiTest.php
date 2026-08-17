<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherOwnPhotoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_teacher_with_permission_can_upload_own_photo(): void
    {
        Storage::fake('public');

        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $token = $teacher->createToken('teacher-app')->plainTextToken;

        $file = UploadedFile::fake()->image('me.jpg', 120, 120);

        $res = $this->withToken($token)
            ->post('/api/teacher/profile', [
                'photo' => $file,
            ]);

        $res->assertOk();
        $res->assertJsonPath('can_edit_own_photo', true);
        $this->assertNotNull($res->json('profile.photo_path'));
        $this->assertNotNull($res->json('profile.photo_url'));
    }

    public function test_teacher_without_permission_gets_403(): void
    {
        Storage::fake('public');

        Role::findByName('Teacher', 'web')->revokePermissionTo('edit teacher own photo');

        $region = Region::create(['name' => 'R']);
        $center = Center::create(['name' => 'C', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'H', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $token = $teacher->createToken('teacher-app')->plainTextToken;

        $file = UploadedFile::fake()->image('me.jpg', 120, 120);

        $res = $this->withToken($token)
            ->post('/api/teacher/profile', [
                'photo' => $file,
            ]);

        $res->assertForbidden();
    }
}
