<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['admin', 'driver', 'passenger'];

        // Create roles with web guard (works for both web and API)
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $adminEmail = env('ADMIN_EMAIL', 'admin@trigo.test');

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
            ]
        );

        // Assign admin role (using web guard - works for both web and API)
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}

