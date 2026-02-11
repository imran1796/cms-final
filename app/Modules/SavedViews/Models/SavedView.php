<?php

namespace App\Modules\SavedViews\Models;

use Illuminate\Database\Eloquent\Model;

final class SavedView extends Model
{
    protected $table = 'saved_views';

    protected $fillable = [
        'space_id',
        'user_id',
        'resource',
        'name',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'space_id' => 'integer',
        'user_id' => 'integer',
    ];
}
