<?php

namespace App\Modules\Content\Events;

use App\Models\Collection;
use Illuminate\Foundation\Events\Dispatchable;

final class ContentTypeCreated
{
    use Dispatchable;

    public function __construct(public readonly Collection $collection)
    {
    }
}
