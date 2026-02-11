<?php

namespace Database\Seeders\System;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = ['Super Admin', 'Admin', 'Editor', 'Author', 'Viewer'];

        $basePermissions = ['manage_users', 'manage_settings', 'manage_spaces', 'manage_forms','manage_assets'];

        // bootstrap permissions for tests/demo
        $bootstrapCollections = ['posts', 'pages', 'products', 'test'];
        $collectionActions = ['create', 'read', 'update', 'delete', 'publish'];

        foreach ($this->allConfiguredGuards() as $guard) {

            // Create base perms
            foreach ($basePermissions as $perm) {
                Permission::findOrCreate($perm, $guard);
            }

            // Create bootstrap collection perms
            foreach ($bootstrapCollections as $handle) {
                foreach ($collectionActions as $action) {
                    Permission::findOrCreate("{$handle}.{$action}", $guard);
                }
            }

            // Create roles
            foreach ($roles as $roleName) {
                Role::findOrCreate($roleName, $guard);
            }

            // Assign permissions safely (by Permission models for that guard)
            $allPermModels = Permission::query()
                ->where('guard_name', $guard)
                ->get();

            Role::findByName('Super Admin', $guard)->syncPermissions($allPermModels);

            // Admin gets base perms (models for that guard)
            $basePermModels = Permission::query()
                ->where('guard_name', $guard)
                ->whereIn('name', $basePermissions)
                ->get();

            Role::findByName('Admin', $guard)->syncPermissions($basePermModels);

            // Others intentionally empty
            Role::findByName('Editor', $guard)->syncPermissions([]);
            Role::findByName('Author', $guard)->syncPermissions([]);
            Role::findByName('Viewer', $guard)->syncPermissions([]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function allConfiguredGuards(): array
    {
        $guards = array_keys((array) config('auth.guards', []));

        // keep only string guard names
        $guards = array_values(array_filter($guards, fn ($g) => is_string($g) && $g !== ''));

        // ensure 'web' is present if your app uses it
        if (!in_array('web', $guards, true)) {
            $guards[] = 'web';
        }

        return array_values(array_unique($guards));
    }
}
