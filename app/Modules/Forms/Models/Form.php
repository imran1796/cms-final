<?php

namespace App\Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;

final class Form extends Model
{
    protected $table = 'forms';

    protected $fillable = [
        'space_id',
        'handle',
        'title',
        'fields',
        'settings',
    ];

    protected $casts = [
        'fields' => 'array',
        'settings' => 'array',
    ];
}
