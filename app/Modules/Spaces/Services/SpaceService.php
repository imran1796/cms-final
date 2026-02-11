<?php

namespace App\Modules\Spaces\Services;

use App\Models\Space;
use App\Modules\Spaces\Repositories\Interfaces\SpaceRepositoryInterface;
use App\Modules\Spaces\Services\Interfaces\SpaceServiceInterface;
use App\Modules\Spaces\Validators\SpaceValidator;
use App\Modules\System\Authorization\Services\AuthorizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;


final class SpaceService implements SpaceServiceInterface
{
    public function __construct(
        private readonly SpaceRepositoryInterface $spaces,
        private readonly AuthorizationService $authz
    ) {
    }

    public function list(): Collection
    {
        $this->authz->requirePermission('manage_spaces');

        return $this->spaces->list();
    }

    public function create(array $input): Space
    {
        $this->authz->requirePermission('manage_spaces');

        $data = SpaceValidator::validateCreate($input);

        $data['storage_prefix'] = 'spaces/' . $data['handle'];
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        DB::beginTransaction();

        try {
            $space = $this->spaces->create($data);

            DB::commit();

            Log::info('Space created', [
                'space_id' => $space->id,
                'handle' => $space->handle,
                'user_id' => auth()->id(),
            ]);

            return $space;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Space create failed', [
                'input' => $input,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $this->authz->requirePermission('manage_spaces');

        DB::beginTransaction();

        try {
            $space = $this->spaces->findOrFail($id);

            $this->spaces->delete($space);

            DB::commit();

            Log::info('Space deleted', [
                'space_id' => $id,
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Space delete failed', [
                'space_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
