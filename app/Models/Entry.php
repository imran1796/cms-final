<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

final class Entry extends Model
{
    use Searchable;

    protected $table = 'entries';

    protected $fillable = [
        'space_id',
        'collection_id',
        'status',
        'published_at',
        'unpublish_at',
        'data',
        'title',
        'slug',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'unpublish_at' => 'datetime',
        'data' => 'array',
    ];

    public function shouldBeSearchable(): bool
    {
        return ($this->status ?? '') === 'published';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'space_id' => (int) $this->space_id,
            'collection_id' => (int) $this->collection_id,
            'status' => $this->status,
            'title' => $this->title ?? '',
            'slug' => $this->slug ?? '',
            'published_at' => $this->published_at?->timestamp,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
