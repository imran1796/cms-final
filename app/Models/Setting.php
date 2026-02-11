<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Setting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
