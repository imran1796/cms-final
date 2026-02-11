<?php

declare(strict_types=1);

namespace App\Modules\Content\Services;

use App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use App\Modules\Content\Repositories\Interfaces\EntryRepositoryInterface;
use App\Modules\Content\Services\Interfaces\EntryLockServiceInterface;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Modules\System\Realtime\Events\EntryLockedRealtimeEvent;
use App\Modules\System\Realtime\Events\EntryUnlockedRealtimeEvent;
use App\Modules\System\Realtime\Services\Interfaces\LockServiceInterface;
use App\Support\Exceptions\EntryLockedApiException;
use App\Support\Exceptions\NotFoundApiException;
use App\Support\Exceptions\ValidationApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class EntryLockService implements EntryLockServiceInterface
{
    private const LOCK_KEY_PREFIX = 'entry_lock:';
    private const OWNER_KEY_PREFIX = 'entry_lock_owner:';

    public function __construct(
        private readonly CollectionRepositoryInterface $collections,
        private readonly EntryRepositoryInterface $entries,
        private readonly LockServiceInterface $locker,
        private readonly AuthorizationService $authz,
    ) {}

    public function lock(string $collectionHandle, int $id, Request $request): array
    {
        $spaceId = $this->requireSpaceId();
        $collection = $this->collections->findByHandle($spaceId, $collectionHandle);
        if (!$collection) {
            throw new NotFoundApiException('Collection not found');
        }

        $this->authz->requirePermission("{$collectionHandle}.read");

        $entry = $this->entries->findOrFail($spaceId, (int) $collection->id, $id);

        $key = self::LOCK_KEY_PREFIX . "{$spaceId}:{$collection->id}:{$id}";
        $ttl = (int) config('cms.entry_lock_ttl_seconds', 300);

        if (!$this->locker->acquire($key, $ttl)) {
            $owner = $this->getOwner($key);
            throw new EntryLockedApiException(
                'Entry is locked by another user',
                ['locked_by' => $owner],
                null
            );
        }

        $user = $request->user();
        $owner = ['id' => $user->id, 'name' => $user->name ?? (string) $user->email];
        Cache::put(self::OWNER_KEY_PREFIX . $key, $owner, $ttl);

        broadcast(new EntryLockedRealtimeEvent(
            spaceId: $spaceId,
            collectionHandle: $collectionHandle,
            entryId: $id,
            userId: $user->id,
            userName: $owner['name'],
        ));

        return [
            'locked' => true,
            'locked_by' => $owner,
        ];
    }

    public function unlock(string $collectionHandle, int $id): array
    {
        $spaceId = $this->requireSpaceId();
        $collection = $this->collections->findByHandle($spaceId, $collectionHandle);
        if (!$collection) {
            throw new NotFoundApiException('Collection not found');
        }

        $this->authz->requirePermission("{$collectionHandle}.read");

        $this->entries->findOrFail($spaceId, (int) $collection->id, $id);

        $key = self::LOCK_KEY_PREFIX . "{$spaceId}:{$collection->id}:{$id}";
        $this->locker->releaseByKey($key);
        Cache::forget(self::OWNER_KEY_PREFIX . $key);

        broadcast(new EntryUnlockedRealtimeEvent(
            spaceId: $spaceId,
            collectionHandle: $collectionHandle,
            entryId: $id,
        ));

        return ['locked' => false];
    }

    private function requireSpaceId(): int
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new ValidationApiException('Validation failed', ['space_id' => ['X-Space-Id required']]);
        }
        return $spaceId;
    }

    private function getOwner(string $lockKey): ?array
    {
        return Cache::get(self::OWNER_KEY_PREFIX . $lockKey);
    }
}
