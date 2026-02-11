<?php

namespace Database\Seeders\System;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Decide which guards to support
        $guards = $this->guards();

        foreach ($guards as $guard) {

            // Ensure Super Admin role exists
            $role = Role::firstOrCreate([
                'name' => 'Super Admin',
                'guard_name' => $guard,
            ]);

            // Ensure all permissions are attached
            $permissions = Permission::where('guard_name', $guard)->get();
            if ($permissions->isNotEmpty()) {
                $role->syncPermissions($permissions);
            }
        }

        // Create or update admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@cms.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('secret123'),
            ]
        );

        // Assign Super Admin role (default guard resolution)
        if (!$user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }

        // Also ensure admin@example.com (Postman/BlogSpaceSeeder) has Super Admin
        $exampleAdmin = User::where('email', 'admin@example.com')->first();
        if ($exampleAdmin && !$exampleAdmin->hasRole('Super Admin')) {
            $exampleAdmin->assignRole('Super Admin');
        }

        // Clear again (important)
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function guards(): array
    {
        $guards = [];
        $guards[] = config('auth.defaults.guard', 'web');

        if (array_key_exists('sanctum', config('auth.guards', []))) {
            $guards[] = 'sanctum';
        }

        return array_unique($guards);
    }
}
