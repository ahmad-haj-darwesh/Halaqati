<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_halaqah_belongs_to_center_in_correct_region(): void
    {
        $region  = Region::create(['name' => 'منطقة الاختبار']);
        $center  = Center::create(['name' => 'مركز الاختبار', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'حلقة الاختبار', 'center_id' => $center->id]);

        $this->assertEquals($center->id, $halaqah->center->id);
        $this->assertEquals($region->id, $halaqah->center->region->id);
    }

    public function test_teacher_profile_resolves_region_through_halaqah(): void
    {
        $region  = Region::create(['name' => 'منطقة']);
        $center  = Center::create(['name' => 'مركز', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'حلقة', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $profile = TeacherProfile::create([
            'user_id'    => $teacher->id,
            'halaqah_id' => $halaqah->id,
        ]);

        $this->assertEquals($region->id, $profile->getRegion()->id);
        $this->assertEquals($center->id, $profile->getCenter()->id);
    }

    public function test_region_has_many_centers_and_halaqahs(): void
    {
        $region   = Region::create(['name' => 'منطقة']);
        $center1  = Center::create(['name' => 'مركز 1', 'region_id' => $region->id]);
        $center2  = Center::create(['name' => 'مركز 2', 'region_id' => $region->id]);
        $halaqah1 = Halaqah::create(['name' => 'حلقة 1', 'center_id' => $center1->id]);
        $halaqah2 = Halaqah::create(['name' => 'حلقة 2', 'center_id' => $center2->id]);

        $region->load('centers', 'halaqahs');

        $this->assertCount(2, $region->centers);
        $this->assertCount(2, $region->halaqahs);
    }

    public function test_halaqah_cannot_belong_to_nonexistent_center(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Halaqah::create(['name' => 'حلقة', 'center_id' => 9999]);
    }

    public function test_teacher_profile_is_unique_per_user(): void
    {
        $region  = Region::create(['name' => 'منطقة']);
        $center  = Center::create(['name' => 'مركز', 'region_id' => $region->id]);
        $halaqah = Halaqah::create(['name' => 'حلقة', 'center_id' => $center->id]);

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        TeacherProfile::create(['user_id' => $teacher->id, 'halaqah_id' => $halaqah->id]);
    }
}
