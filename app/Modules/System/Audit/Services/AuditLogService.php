<?php

namespace App\Modules\System\Audit\Services;

use App\Models\AuditLog;
use App\Modules\System\Audit\Repositories\Interfaces\AuditLogRepositoryInterface;
use App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface;
use Illuminate\Support\Facades\Log;

final class AuditLogService implements AuditLogServiceInterface
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repo
    ) {
    }

    public function write(
        string $action,
        string $resource,
        array $diff = [],
        ?int $spaceId = null,
        ?int $actorId = null
    ): AuditLog {
        $resolvedSpaceId = $spaceId ?? \App\Support\CurrentSpace::id();
        $resolvedActorId = $actorId ?? auth()->id();

        $log = $this->repo->create([
            'space_id' => $resolvedSpaceId,
            'actor_id' => $resolvedActorId,
            'action' => $action,
            'resource' => $resource,
            'diff' => empty($diff) ? null : $diff,
        ]);

        Log::channel('audit')->info('Audit log written', [
            'audit_log_id' => $log->id,
            'space_id' => $resolvedSpaceId,
            'actor_id' => $resolvedActorId,
            'action' => $action,
            'resource' => $resource,
        ]);

        return $log;
    }
}
