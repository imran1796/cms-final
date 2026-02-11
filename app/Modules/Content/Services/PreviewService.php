<?php

declare(strict_types=1);

namespace App\Modules\Content\Services;

use App\Models\Entry;
use App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use App\Modules\Content\Repositories\Interfaces\EntryRepositoryInterface;
use App\Modules\Content\Services\Interfaces\PreviewServiceInterface;
use App\Support\Exceptions\ForbiddenApiException;
use App\Support\Exceptions\NotFoundApiException;
use App\Support\Exceptions\ValidationApiException;

final class PreviewService implements PreviewServiceInterface
{
    public function __construct(
        private readonly PreviewTokenService $tokens,
        private readonly CollectionRepositoryInterface $collections,
        private readonly EntryRepositoryInterface $entries,
    ) {}

    public function preview(int $spaceId, string $collectionHandle, int $id, string $token): Entry
    {
        if ($spaceId <= 0) {
            throw new ValidationApiException('Validation failed', ['space_id' => ['X-Space-Id required']]);
        }

        if (!$this->tokens->validate($token, $spaceId, $collectionHandle, $id)) {
            throw new ForbiddenApiException('Invalid preview token');
        }

        $collection = $this->collections->findByHandle($spaceId, $collectionHandle);
        if (!$collection) {
            throw new NotFoundApiException('Collection not found');
        }

        return $this->entries->findOrFail($spaceId, (int) $collection->id, $id);
    }
}
