<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view users', 'create users', 'edit users', 'delete users',
            'view regions', 'create regions', 'edit regions', 'delete regions',
            'view centers', 'create centers', 'edit centers', 'delete centers',
            'view halaqahs', 'create halaqahs', 'edit halaqahs', 'delete halaqahs',
            'view teacher_profiles', 'create teacher_profiles', 'edit teacher_profiles', 'delete teacher_profiles',
            'edit teacher own photo',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $eduSupervisor = Role::firstOrCreate(['name' => 'EducationalSupervisor', 'guard_name' => 'web']);
        $centerSupervisor = Role::firstOrCreate(['name' => 'CenterSupervisor', 'guard_name' => 'web']);
        $examiner = Role::firstOrCreate(['name' => 'Examiner', 'guard_name' => 'web']);
        $teacher = Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);

        $admin->syncPermissions([
            'view users', 'create users', 'edit users', 'delete users',
            'view regions', 'create regions', 'edit regions', 'delete regions',
            'view centers', 'create centers', 'edit centers', 'delete centers',
            'view halaqahs', 'create halaqahs', 'edit halaqahs', 'delete halaqahs',
            'view teacher_profiles', 'create teacher_profiles', 'edit teacher_profiles', 'delete teacher_profiles',
        ]);

        $eduSupervisor->syncPermissions([
            'view centers', 'view halaqahs', 'view teacher_profiles',
        ]);

        $centerSupervisor->syncPermissions([
            'view centers', 'view halaqahs', 'view teacher_profiles',
        ]);

        $examiner->syncPermissions([
            'view halaqahs', 'view teacher_profiles',
        ]);

        $teacher->syncPermissions([
            'edit teacher own photo',
        ]);
    }
}
