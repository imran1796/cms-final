<?php

namespace App\Modules\System\Audit\Services\Interfaces;

use App\Models\AuditLog;

interface AuditLogServiceInterface
{
    public function write(
        string $action,
        string $resource,
        array $diff = [],
        ?int $spaceId = null,
        ?int $actorId = null
    ): AuditLog;
}
