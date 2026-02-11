<?php

namespace App\Modules\System\Realtime\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

final class SystemAlertRealtimeEvent implements ShouldBroadcast
{
    public function __construct(
        public readonly string $type,
        public readonly string $message,
        public readonly ?string $link = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('admin')];
    }

    public function broadcastAs(): string
    {
        return 'system.alert';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'type' => $this->type,
            'message' => $this->message,
        ];
        if ($this->link !== null) {
            $payload['link'] = $this->link;
        }
        return $payload;
    }
}
