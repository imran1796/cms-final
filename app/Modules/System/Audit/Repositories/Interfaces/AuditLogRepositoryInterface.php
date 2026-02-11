<?php

namespace App\Modules\System\Audit\Repositories\Interfaces;

use App\Models\AuditLog;

interface AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog;
}
