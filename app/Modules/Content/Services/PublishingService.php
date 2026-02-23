<?php

namespace App\Modules\Content\Services;

use App\Models\Entry;
use App\Modules\Content\Events\EntryPublished;
use App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface;
use App\Modules\System\Authorization\Services\AuthorizationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PublishingService implements PublishingServiceInterface
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly AuditLogServiceInterface $audit
    ) {}

    public function publish(string $collectionHandle, int $id): Entry
    {
        $spaceId = $this->requireSpaceId();

        $this->authz->requirePermission("{$collectionHandle}.publish");

        $entry = $this->findEntryOrFail($spaceId, $collectionHandle, $id);
        $before = $entry->toArray();

        DB::beginTransaction();
        try {
            $entry->status = 'published';
            $entry->published_at = $entry->published_at ?: Carbon::now();
            $entry->save();

            $this->audit->write(
                action: 'entry.publish',
                resource: $collectionHandle,
                diff: ['before' => $before, 'after' => $entry->toArray()],
                spaceId: $spaceId,
                actorId: auth()->id()
            );

            DB::commit();

            event(new EntryPublished($entry, $collectionHandle, $spaceId));

            Log::info('Entry published', [
                'space_id' => $spaceId,
                'handle' => $collectionHandle,
                'entry_id' => $entry->id,
                'user_id' => auth()->id(),
            ]);

            return $entry->refresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Publish failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function unpublish(string $collectionHandle, int $id): Entry
    {
        $spaceId = $this->requireSpaceId();

        $this->authz->requirePermission("{$collectionHandle}.publish");

        $entry = $this->findEntryOrFail($spaceId, $collectionHandle, $id);
        $before = $entry->toArray();

        DB::beginTransaction();
        try {
            $entry->status = 'draft';
            $entry->published_at = null;
            $entry->unpublish_at = null;
            $entry->save();

            $this->audit->write(
                action: 'entry.unpublish',
                resource: $collectionHandle,
                diff: ['before' => $before, 'after' => $entry->toArray()],
                spaceId: $spaceId,
                actorId: auth()->id()
            );

            DB::commit();

            Log::info('Entry unpublished', [
                'space_id' => $spaceId,
                'handle' => $collectionHandle,
                'entry_id' => $entry->id,
                'user_id' => auth()->id(),
            ]);

            return $entry->refresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unpublish failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function publishScheduled(): int
    {
        $now = Carbon::now();

        $eligibleIds = Entry::query()
            ->where(function ($q) use ($now) {
                $q->where(function ($scheduled) use ($now) {
                    $scheduled->where('status', 'scheduled')
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', $now);
                });
                // Legacy fallback intentionally disabled after migration to real scheduled status.
                // Re-enable this block only if you still need to auto-publish draft+due entries:
                // $q->orWhere(function ($legacy) use ($now) {
                //     $legacy->where('status', 'draft')
                //         ->whereNotNull('published_at')
                //         ->where('published_at', '<=', $now);
                // });
            })
            ->orderBy('published_at')
            ->orderBy('id')
            ->limit(500)
            ->pluck('id')
            ->all();

        $count = 0;

        foreach ($eligibleIds as $entryId) {
            $entry = Entry::query()->find($entryId);
            if (!$entry) {
                continue;
            }

            $updated = Entry::query()
                ->where('id', $entryId)
                ->where('status', 'scheduled')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', $now)
                ->update([
                    'status' => 'published',
                    'updated_at' => $now,
                ]);

            if ($updated === 0) {
                continue;
            }

            $entry = Entry::query()->find($entryId);
            if (!$entry) {
                continue;
            }

            $collection = \App\Models\Collection::query()->find($entry->collection_id);
            if (!$collection) {
                continue;
            }

            try {
                event(new EntryPublished($entry, $collection->handle, (int)$entry->space_id));
                Log::info('Scheduled publish executed', [
                    'space_id' => $entry->space_id,
                    'handle' => $collection->handle,
                    'entry_id' => $entry->id,
                ]);

                $count++;
            } catch (\Throwable $e) {
                Log::error('Scheduled publish failed', [
                    'entry_id' => $entry->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    public function unpublishScheduled(): int
    {
        $now = Carbon::now();

        $eligibleIds = Entry::query()
            ->where('status', 'published')
            ->whereNotNull('unpublish_at')
            ->where('unpublish_at', '<=', $now)
            ->orderBy('unpublish_at')
            ->orderBy('id')
            ->limit(500)
            ->pluck('id')
            ->all();

        $count = 0;

        foreach ($eligibleIds as $entryId) {
            $updated = Entry::query()
                ->where('id', $entryId)
                ->where('status', 'published')
                ->whereNotNull('unpublish_at')
                ->where('unpublish_at', '<=', $now)
                ->update([
                    'status' => 'draft',
                    'published_at' => null,
                    'unpublish_at' => null,
                    'updated_at' => $now,
                ]);

            if ($updated === 0) {
                continue;
            }

            try {
                Log::info('Scheduled unpublish executed', [
                    'entry_id' => $entryId,
                ]);

                $count++;
            } catch (\Throwable $e) {
                Log::error('Scheduled unpublish failed', [
                    'entry_id' => $entryId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    private function requireSpaceId(): int
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            abort(422, 'Missing space context (X-Space-Id)');
        }
        return $spaceId;
    }

    private function findEntryOrFail(int $spaceId, string $collectionHandle, int $id): Entry
    {
        $collection = \App\Models\Collection::query()
            ->where('space_id', $spaceId)
            ->where('handle', $collectionHandle)
            ->firstOrFail();

        return Entry::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collection->id)
            ->where('id', $id)
            ->firstOrFail();
    }
}


