<?php

namespace App\Modules\ContentTree\Models;

use Illuminate\Database\Eloquent\Model;

final class ContentTreeNode extends Model
{
    protected $table = 'content_tree_nodes';

    protected $fillable = [
        'space_id',
        'collection_id',
        'entry_id',
        'parent_id',
        'position',
        'path',
    ];
}
