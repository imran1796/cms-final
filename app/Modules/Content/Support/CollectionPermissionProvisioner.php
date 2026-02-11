<?php

namespace App\Modules\Content\Support;

use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

final class CollectionPermissionProvisioner
{
    public static function provision(string $handle): void
    {
        $perms = [
            "{$handle}.create",
            "{$handle}.read",
            "{$handle}.update",
            "{$handle}.delete",
            "{$handle}.publish",
        ];

        $guards = self::targetGuards();

        foreach ($guards as $guard) {
        foreach ($perms as $name) {
                Permission::findOrCreate($name, $guard);
                Log::info('Collection permission created', ['permission' => $name, 'guard' => $guard]);
            }
        }
    }

    private static function targetGuards(): array
    {
        $guards = [];
        $default = (string) config('auth.defaults.guard', 'web');
        $guards[] = $default;

        $allGuards = array_keys((array) config('auth.guards', []));

        if (in_array('sanctum', $allGuards, true)) {
            $guards[] = 'sanctum';
            }

        return array_values(array_unique($guards));
    }
}
