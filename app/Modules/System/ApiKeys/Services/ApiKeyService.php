<?php

namespace App\Modules\System\ApiKeys\Services;

use App\Models\ApiKey;
use App\Modules\System\ApiKeys\Repositories\Interfaces\ApiKeyRepositoryInterface;
use App\Modules\System\ApiKeys\Services\Interfaces\ApiKeyServiceInterface;
use App\Modules\System\ApiKeys\Validators\ApiKeyValidator;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Support\Exceptions\NotFoundApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ApiKeyService implements ApiKeyServiceInterface
{
    public function __construct(
        private readonly ApiKeyRepositoryInterface $repo,
        private readonly AuthorizationService $authz
    ) {
    }

    private function requireSpaceId(): int
    {
        return \App\Support\CurrentSpace::requireId();
    }

    public function list(): Collection
    {
        $this->authz->requirePermission('manage_settings');
        $spaceId = $this->requireSpaceId();
        return $this->repo->listBySpace($spaceId);
    }

    public function create(array $input): array
    {
        $this->authz->requirePermission('manage_settings');
        $spaceId = $this->requireSpaceId();

        $data = ApiKeyValidator::validateCreate($input);
        $data['space_id'] = $spaceId;

        DB::beginTransaction();

        try {
            [$plain, $hash] = $this->generateTokenPair();

            $apiKey = $this->repo->create([
                'space_id' => $data['space_id'],
                'name' => $data['name'],
                'token_hash' => $hash,
                'scopes' => $data['scopes'] ?? null,
            ]);

            DB::commit();

            Log::info('API key created', [
                'api_key_id' => $apiKey->id,
                'space_id' => $apiKey->space_id,
                'user_id' => auth()->id(),
            ]);

            return [
                'api_key' => $apiKey,
                'plain_token' => $plain,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('API key create failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function update(int $id, array $input): ApiKey
    {
        $this->authz->requirePermission('manage_settings');
        $spaceId = $this->requireSpaceId();

        $data = ApiKeyValidator::validateUpdate($input);

        DB::beginTransaction();

        try {
            $apiKey = $this->repo->findOrFailForSpace($spaceId, $id);

            $updated = $this->repo->update($apiKey, [
                'name' => $data['name'] ?? $apiKey->name,
                'scopes' => array_key_exists('scopes', $data) ? $data['scopes'] : $apiKey->scopes,
            ]);

            DB::commit();

            Log::info('API key updated', [
                'api_key_id' => $updated->id,
                'user_id' => auth()->id(),
            ]);

            return $updated;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('API key update failed', [
                'api_key_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $this->authz->requirePermission('manage_settings');
        $spaceId = $this->requireSpaceId();

        DB::beginTransaction();

        try {
            $apiKey = $this->repo->findOrFailForSpace($spaceId, $id);
            $this->repo->delete($apiKey);

            DB::commit();

            Log::info('API key deleted', [
                'api_key_id' => $id,
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('API key delete failed', [
                'api_key_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function regenerate(int $id): array
    {
        $this->authz->requirePermission('manage_settings');
        $spaceId = $this->requireSpaceId();

        DB::beginTransaction();

        try {
            $apiKey = $this->repo->findOrFailForSpace($spaceId, $id);

            [$plain, $hash] = $this->generateTokenPair();

            $apiKey = $this->repo->update($apiKey, [
                'token_hash' => $hash,
            ]);

            DB::commit();

            Log::info('API key regenerated', [
                'api_key_id' => $apiKey->id,
                'user_id' => auth()->id(),
            ]);

            return [
                'api_key' => $apiKey,
                'plain_token' => $plain,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('API key regenerate failed', [
                'api_key_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function generateTokenPair(): array
    {
        $plain = 'ak_' . Str::random(48);
        $hash = hash('sha256', $plain);

        return [$plain, $hash];
    }
}
