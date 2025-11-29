<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles & permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $perms = [
            'view profile',
            'edit profile',
            'manage users',
            'manage roles',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // create roles and assign created permissions
        // NOTE: This seeder creates 'user' role, but the app uses 'passenger', 'driver', 'admin'
        // Consider using RolesAndAdminSeeder instead for this application
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userRole->givePermissionTo('view profile');

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        // admin gets everything (adjust as you like)
        $adminRole->givePermissionTo(Permission::all());

        // OPTIONAL: create a default admin user (change email/password)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'), // change to secure password
            ]
        );

        // assign role to the admin
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // OPTIONAL: create a default normal user
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Normal User',
                'password' => Hash::make('password123'),
            ]
        );

        if (! $user->hasRole('user')) {
            $user->assignRole('user');
        }
    }
}
