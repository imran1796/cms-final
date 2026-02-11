<?php

namespace App\Modules\Content\Services;

use App\Models\Collection;
use App\Modules\Content\Events\ContentTypeCreated;
use App\Modules\Content\Events\ContentTypeUpdated;
use App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use App\Modules\Content\Support\CollectionPermissionProvisioner;
use App\Modules\Content\Validators\ContentTypeValidator;
use App\Modules\System\Authorization\Services\AuthorizationService;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ContentTypeService implements ContentTypeServiceInterface
{
    public function __construct(
        private readonly CollectionRepositoryInterface $repo,
        private readonly AuthorizationService $authz
    ) {
    }

    public function list(): SupportCollection
    {
        $this->authz->requirePermission('manage_settings');

        $spaceId = \App\Support\CurrentSpace::id();

        return $this->repo->list($spaceId);
    }

    public function create(array $input): Collection
    {
        $this->authz->requirePermission('manage_settings');

        $spaceId = \App\Support\CurrentSpace::id();
        $data = ContentTypeValidator::validateCreate($input, $spaceId);

        DB::beginTransaction();
        try {
            $fields = $this->normalizeFields($data['fields'] ?? []);

            $collection = $this->repo->create([
                'space_id' => $spaceId,
                'handle' => $data['handle'],
                'type' => $data['type'],
                'fields' => $fields,
                'settings' => $data['settings'] ?? null,
            ]);

            CollectionPermissionProvisioner::provision($collection->handle);

            event(new ContentTypeCreated($collection));

            DB::commit();

            Log::info('Collection created', [
                'collection_id' => $collection->id,
                'space_id' => $spaceId,
                'handle' => $collection->handle,
                'user_id' => auth()->id(),
            ]);

            return $collection;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Collection create failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function get(int $id): Collection
    {
        $this->authz->requirePermission('manage_settings');

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null) {
            abort(404);
        }

        return $this->repo->findOrFailForSpace((int) $spaceId, $id);
    }

    public function update(int $id, array $input): Collection
    {
        $this->authz->requirePermission('manage_settings');

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null) {
            abort(404);
        }

        $collection = $this->repo->findOrFailForSpace((int) $spaceId, $id);
        $data = ContentTypeValidator::validateUpdate($input, $spaceId, $collection->id);

        DB::beginTransaction();
        try {
            $updated = $this->repo->update($collection, [
                'handle' => $data['handle'] ?? $collection->handle,
                'type' => $data['type'] ?? $collection->type,
                'fields' => array_key_exists('fields', $data) ? $this->normalizeFields($data['fields'] ?? []) : $collection->fields,
                'settings' => array_key_exists('settings', $data) ? ($data['settings'] ?? null) : $collection->settings,
            ]);

            if (isset($data['handle']) && $data['handle'] !== $collection->handle) {
                CollectionPermissionProvisioner::provision($data['handle']);
            }

            event(new ContentTypeUpdated($updated));

            DB::commit();

            Log::info('Collection updated', [
                'collection_id' => $updated->id,
                'space_id' => $spaceId,
                'handle' => $updated->handle,
                'user_id' => auth()->id(),
            ]);

            return $updated;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Collection update failed', [
                'collection_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $this->authz->requirePermission('manage_settings');

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null) {
            abort(404);
        }

        $collection = $this->repo->findOrFailForSpace((int) $spaceId, $id);

        DB::beginTransaction();
        try {
            $this->repo->delete($collection);

            DB::commit();

            Log::info('Collection deleted', [
                'collection_id' => $id,
                'space_id' => $spaceId,
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Collection delete failed', [
                'collection_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function addField(int $id, array $fieldInput): Collection
    {
        $this->authz->requirePermission('manage_settings');

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null) {
            abort(404);
        }

        $collection = $this->repo->findOrFailForSpace((int) $spaceId, $id);

        $field = ContentTypeValidator::validateField($fieldInput);
        $field['id'] = (string) Str::uuid();
        $field['required'] = (bool) ($field['required'] ?? false);

        DB::beginTransaction();
        try {
            $fields = $collection->fields ?? [];

            foreach ($fields as $f) {
                if (($f['handle'] ?? null) === $field['handle']) {
                    abort(422, 'Field handle already exists');
                }
            }

            $fields[] = $field;

            $updated = $this->repo->update($collection, ['fields' => $fields]);

            event(new ContentTypeUpdated($updated));
            DB::commit();

            Log::info('Collection field added', [
                'collection_id' => $id,
                'field_id' => $field['id'],
                'space_id' => $spaceId,
                'user_id' => auth()->id(),
            ]);

            return $updated;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Add field failed', [
                'collection_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function updateField(int $id, string $fieldId, array $fieldInput): Collection
    {
        $this->authz->requirePermission('manage_settings');

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null) {
            abort(404);
        }

        $collection = $this->repo->findOrFailForSpace((int) $spaceId, $id);

        $patch = ContentTypeValidator::validateField($fieldInput);

        DB::beginTransaction();
        try {
            $fields = $collection->fields ?? [];
            $found = false;

            foreach ($fields as $i => $f) {
                if (($f['id'] ?? null) === $fieldId) {
                    if (($patch['handle'] ?? null) !== ($f['handle'] ?? null)) {
                        foreach ($fields as $other) {
                            if (($other['id'] ?? null) !== $fieldId && ($other['handle'] ?? null) === $patch['handle']) {
                                abort(422, 'Field handle already exists');
                            }
                        }
                    }

                    $fields[$i] = array_merge($f, $patch);
                    $fields[$i]['required'] = (bool) ($fields[$i]['required'] ?? false);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                abort(404);
            }

            $updated = $this->repo->update($collection, ['fields' => $fields]);

            event(new ContentTypeUpdated($updated));
            DB::commit();

            Log::info('Collection field updated', [
                'collection_id' => $id,
                'field_id' => $fieldId,
                'space_id' => $spaceId,
                'user_id' => auth()->id(),
            ]);

            return $updated;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Update field failed', [
                'collection_id' => $id,
                'field_id' => $fieldId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function deleteField(int $id, string $fieldId): Collection
    {
        $this->authz->requirePermission('manage_settings');

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null) {
            abort(404);
        }

        $collection = $this->repo->findOrFailForSpace((int) $spaceId, $id);

        DB::beginTransaction();
        try {
            $fields = $collection->fields ?? [];
            $new = [];
            $found = false;

            foreach ($fields as $f) {
                if (($f['id'] ?? null) === $fieldId) {
                    $found = true;
                    continue;
                }
                $new[] = $f;
            }

            if (!$found) {
                abort(404);
            }

            $updated = $this->repo->update($collection, ['fields' => $new]);

            event(new ContentTypeUpdated($updated));
            DB::commit();

            Log::info('Collection field deleted', [
                'collection_id' => $id,
                'field_id' => $fieldId,
                'space_id' => $spaceId,
                'user_id' => auth()->id(),
            ]);

            return $updated;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delete field failed', [
                'collection_id' => $id,
                'field_id' => $fieldId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function normalizeFields(array $fields): array
    {
        $out = [];
        $handles = [];

        foreach ($fields as $fieldInput) {
            $f = ContentTypeValidator::validateField(is_array($fieldInput) ? $fieldInput : []);
            $f['id'] = $f['id'] ?? (string) Str::uuid();
            $f['required'] = (bool) ($f['required'] ?? false);

            if (in_array($f['handle'], $handles, true)) {
                abort(422, 'Duplicate field handle in fields array');
            }
            $handles[] = $f['handle'];

            $out[] = $f;
        }

        return $out;
    }
}
