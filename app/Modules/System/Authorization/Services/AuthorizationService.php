<?php

namespace App\Modules\System\Authorization\Services;

use App\Support\Exceptions\ForbiddenApiException;

final class AuthorizationService
{
    public function requirePermission(string $permission): void
    {
        $user = auth()->user();

        if (!$user) {
            throw new ForbiddenApiException('Forbidden: unauthenticated');
        }

        if ($user->hasRole('Super Admin')) {
            return;
        }

        if (!$user->can($permission)) {
            throw new ForbiddenApiException('Forbidden: missing permission ' . $permission);
        }
    }

    public function requireAnyPermission(array $permissions): void
    {
        $user = auth()->user();

        if (!$user) {
            throw new ForbiddenApiException('Forbidden: unauthenticated');
        }

        if ($user->hasRole('Super Admin')) {
            return;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return;
            }
        }

        throw new ForbiddenApiException('Forbidden: missing required permission');
    }
}
