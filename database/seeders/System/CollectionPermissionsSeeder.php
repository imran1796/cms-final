<?php

namespace Database\Seeders\System;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class CollectionPermissionsSeeder extends Seeder
{
    /**
     * Add default handles here for testing / bootstrap.
     * In production, Phase 6 should auto-provision on collection create.
     */
    private array $defaultHandles = [
        'posts',
        'test',
        // 'pages',
        // 'products',
    ];

    private array $actions = ['create', 'read', 'update', 'delete', 'publish'];

    public function run(): void
    {
        // Always reset cached permissions before/after seeding.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guards = $this->targetGuards();

        foreach ($guards as $guard) {
            foreach ($this->defaultHandles as $handle) {
                foreach ($this->actions as $action) {
                    Permission::findOrCreate("{$handle}.{$action}", $guard);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Create permissions for default guard + sanctum if it exists.
     * This makes your permissions work for API requests today and remains compatible if you later switch guards.
     */
    private function targetGuards(): array
    {
        $guards = [];

        $default = (string) config('auth.defaults.guard', 'web');
        $guards[] = $default;

        $allGuards = array_keys((array) config('auth.guards', []));

        // Only add sanctum if it's defined as a guard in config/auth.php
        if (in_array('sanctum', $allGuards, true)) {
            $guards[] = 'sanctum';
        }

        return array_values(array_unique($guards));
    }
}
