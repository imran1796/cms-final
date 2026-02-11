<?php

namespace App\Modules\SavedViews\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Modules\SavedViews\Models\SavedView;
use App\Modules\SavedViews\Validators\SavedViewValidator;
use App\Modules\SavedViews\Repositories\Interfaces\SavedViewRepositoryInterface;
use App\Modules\SavedViews\Services\Interfaces\SavedViewServiceInterface;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Support\Exceptions\ForbiddenApiException;

final class SavedViewService implements SavedViewServiceInterface
{
    public function __construct(
        private readonly SavedViewRepositoryInterface $views,
        private readonly AuthorizationService $authz,
    ) {}

    private function requireUserId(): int
    {
        $id = auth()->id();
        if (!$id) {
            throw new ForbiddenApiException('Unauthenticated');
        }
        return (int) $id;
    }

    private function spaceIdFromContext(): ?int
    {
        return \App\Support\CurrentSpace::id();
    }

    public function list(?string $resource): Collection
    {
        $this->authz->requirePermission('manage_settings');

        $userId = $this->requireUserId();
        $spaceId = $this->spaceIdFromContext();

        return $this->views->list($userId, $spaceId, $resource);
    }

    public function create(array $input): SavedView
    {
        $this->authz->requirePermission('manage_settings');

        $userId = $this->requireUserId();
        $spaceId = $this->spaceIdFromContext();

        $validated = SavedViewValidator::validateCreate($input);

        return DB::transaction(function () use ($validated, $userId, $spaceId) {
            $view = $this->views->create([
                'space_id' => $spaceId,
                'user_id' => $userId,
                'resource' => $validated['resource'],
                'name' => $validated['name'],
                'config' => $validated['config'],
            ]);

            Log::info('Saved view created', [
                'space_id' => $spaceId,
                'user_id' => $userId,
                'saved_view_id' => $view->id,
                'resource' => $view->resource,
            ]);

            return $view;
        });
    }

    public function update(int $id, array $input): SavedView
    {
        $this->authz->requirePermission('manage_settings');

        $userId = $this->requireUserId();
        $spaceId = $this->spaceIdFromContext();

        $validated = SavedViewValidator::validateUpdate($input);

        return DB::transaction(function () use ($id, $validated, $userId, $spaceId) {
            $view = $this->views->findOwnedOrFail($id, $userId, $spaceId);

            $updated = $this->views->update($view, $validated);

            Log::info('Saved view updated', [
                'space_id' => $spaceId,
                'user_id' => $userId,
                'saved_view_id' => $updated->id,
            ]);

            return $updated;
        });
    }

    public function delete(int $id): void
    {
        $this->authz->requirePermission('manage_settings');

        $userId = $this->requireUserId();
        $spaceId = $this->spaceIdFromContext();

        DB::transaction(function () use ($id, $userId, $spaceId) {
            $view = $this->views->findOwnedOrFail($id, $userId, $spaceId);
            $this->views->delete($view);

            Log::info('Saved view deleted', [
                'space_id' => $spaceId,
                'user_id' => $userId,
                'saved_view_id' => $id,
            ]);
        });
    }
}
