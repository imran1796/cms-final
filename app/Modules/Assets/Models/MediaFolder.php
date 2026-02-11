<?php

namespace App\Modules\Assets\Models;

use Illuminate\Database\Eloquent\Model;

final class MediaFolder extends Model
{
    protected $table = 'media_folders';

    protected $fillable = [
        'space_id','parent_id','name','path',
    ];
}
