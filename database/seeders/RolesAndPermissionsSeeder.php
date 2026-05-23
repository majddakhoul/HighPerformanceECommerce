<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'api';

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guardName]);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => $guardName]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password')
            ]
        );

        $admin->assignRole($adminRole);
    }
}
