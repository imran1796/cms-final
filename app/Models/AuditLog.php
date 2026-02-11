<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AuditLog extends Model
{
    protected $fillable = [
        'space_id',
        'actor_id',
        'action',
        'resource',
        'diff',
    ];

    protected $casts = [
        'diff' => 'array',
    ];
}
