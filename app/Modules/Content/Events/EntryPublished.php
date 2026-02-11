<?php

namespace App\Modules\Content\Events;

use App\Models\Entry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EntryPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Entry $entry,
        public readonly string $collectionHandle,
        public readonly int $spaceId
    ) {}
}
