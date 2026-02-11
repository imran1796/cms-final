<?php

namespace App\Modules\Assets\Models;

use Illuminate\Database\Eloquent\Model;

final class MediaVariant extends Model
{
    protected $table = 'media_variants';

    protected $fillable = [
        'media_id','preset_key','transform_key','transform',
        'disk','path','mime','size','width','height','meta',
    ];

    protected $casts = [
        'transform' => 'array',
        'meta' => 'array',
    ];
}
