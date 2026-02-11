<?php

namespace App\Modules\Content\Listeners;

use App\Modules\Content\Contracts\CacheInvalidatorInterface;
use App\Modules\Content\Contracts\WebhookDispatcherInterface;
use App\Modules\Content\Events\EntryPublished;
use App\Modules\System\Realtime\Events\EntryPublishedRealtimeEvent;
use Illuminate\Support\Facades\Log;

final class EntryPublishedListener
{
    public function __construct(
        private readonly CacheInvalidatorInterface $cache,
        private readonly WebhookDispatcherInterface $webhooks
    ) {}

    public function handle(EntryPublished $event): void
    {
        try {
            $this->cache->invalidateEntry($event->spaceId, $event->collectionHandle, $event->entry);
        } catch (\Throwable $e) {
            Log::warning('Cache invalidation failed', ['message' => $e->getMessage()]);
        }

        try {
            $this->webhooks->dispatchEntryPublished($event->spaceId, $event->collectionHandle, $event->entry);
        } catch (\Throwable $e) {
            Log::warning('Webhook dispatch failed', ['message' => $e->getMessage()]);
        }

        try {
            broadcast(new EntryPublishedRealtimeEvent(
                spaceId: $event->spaceId,
                collectionHandle: $event->collectionHandle,
                entryId: (int) $event->entry->id,
            ));
        } catch (\Throwable $e) {
            Log::warning('Realtime broadcast failed', ['message' => $e->getMessage()]);
        }
    }
}
