<?php

namespace App\Modules\System\Audit\Repositories;

use App\Models\AuditLog;
use App\Modules\System\Audit\Repositories\Interfaces\AuditLogRepositoryInterface;

final class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }
}
