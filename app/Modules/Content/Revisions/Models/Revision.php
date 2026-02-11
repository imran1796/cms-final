<?php

namespace App\Modules\Content\Revisions\Models;

use Illuminate\Database\Eloquent\Model;

final class Revision extends Model
{
    protected $table = 'revisions';

    protected $fillable = [
        'space_id',
        'entry_id',
        'snapshot',
        'diff',
        'created_by',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'diff' => 'array',
    ];
}
