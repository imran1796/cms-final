<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Space extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'handle',
        'name',
        'settings',
        'storage_prefix',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];
}
