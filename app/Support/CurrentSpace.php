<?php

namespace App\Support;

use App\Models\Space;
use App\Support\Exceptions\NotFoundApiException;

final class CurrentSpace
{
    public static function id(): ?int
    {
        if (!app()->bound('currentSpaceId')) {
            return null;
        }
        $id = app('currentSpaceId');
        return $id !== null ? (int) $id : null;
    }

    public static function get(): ?Space
    {
        if (!app()->bound('currentSpace')) {
            return null;
        }
        $space = app('currentSpace');
        return $space instanceof Space ? $space : null;
    }

    public static function requireId(): int
    {
        $id = self::id();
        if ($id === null || $id <= 0) {
            throw new NotFoundApiException('Space context required. Send X-Space-Id header.');
        }
        return $id;
    }
}
