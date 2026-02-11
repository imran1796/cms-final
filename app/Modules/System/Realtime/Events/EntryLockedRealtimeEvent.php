<?php

namespace App\Modules\System\Realtime\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

final class EntryLockedRealtimeEvent implements ShouldBroadcast
{
    public function __construct(
        public readonly int $spaceId,
        public readonly string $collectionHandle,
        public readonly int $entryId,
        public readonly int $userId,
        public readonly string $userName,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('space.' . $this->spaceId)];
    }

    public function broadcastAs(): string
    {
        return 'entry.locked';
    }

    public function broadcastWith(): array
    {
        return [
            'space_id' => $this->spaceId,
            'collection' => $this->collectionHandle,
            'entry_id' => $this->entryId,
            'locked_by' => [
                'id' => $this->userId,
                'name' => $this->userName,
            ],
        ];
    }
}
