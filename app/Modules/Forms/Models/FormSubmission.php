<?php

namespace App\Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;

final class FormSubmission extends Model
{
    protected $table = 'form_submissions';

    protected $fillable = [
        'space_id',
        'form_id',
        'status',
        'data',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'meta' => 'array',
    ];
}
