<?php

namespace App\Modules\Assets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'space_id','folder_id','disk','path','filename','mime','size',
        'checksum','kind','width','height','duration','meta','created_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
