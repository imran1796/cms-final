<?php

namespace App\Modules\System\Realtime\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

final class EntryUpdatedRealtimeEvent implements ShouldBroadcast
{
    public function __construct(
        public readonly int $spaceId,
        public readonly string $collectionHandle,
        public readonly int $entryId,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('space.' . $this->spaceId)];
    }

    public function broadcastAs(): string
    {
        return 'entry.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'space_id' => $this->spaceId,
            'collection' => $this->collectionHandle,
            'entry_id' => $this->entryId,
        ];
    }
}
