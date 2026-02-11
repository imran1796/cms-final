<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ApiKey extends Model
{
    protected $fillable = [
        'space_id',
        'name',
        'token_hash',
        'scopes',
    ];

    protected $casts = [
        'scopes' => 'array',
    ];
}
