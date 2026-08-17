<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $superAdmin->assignRole('SuperAdmin');

        $this->command->info('SuperAdmin created: superadmin@gmail.com / password');
    }
}
