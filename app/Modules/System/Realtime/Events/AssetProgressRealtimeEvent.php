<?php

namespace App\Modules\System\Realtime\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

final class AssetProgressRealtimeEvent implements ShouldBroadcast
{
    public function __construct(
        public readonly int $spaceId,
        public readonly int $assetId,
        public readonly int $progress,
        public readonly string $stage,
        public readonly ?string $uploadId = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('space.' . $this->spaceId)];
    }

    public function broadcastAs(): string
    {
        return 'asset.progress';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'space_id' => $this->spaceId,
            'asset_id' => $this->assetId,
            'progress' => $this->progress,
            'stage' => $this->stage,
        ];
        if ($this->uploadId !== null) {
            $payload['upload_id'] = $this->uploadId;
        }
        return $payload;
    }
}
