<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Collection extends Model
{
    protected $table = 'collections';

    protected $fillable = [
        'space_id',
        'handle',
        'type',
        'fields',
        'settings',
    ];

    protected $casts = [
        'fields' => 'array',
        'settings' => 'array',
    ];
}
