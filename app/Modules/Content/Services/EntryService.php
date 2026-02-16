<?php

namespace App\Modules\Content\Services;

use App\Models\Collection;
use App\Models\Entry;
use App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use App\Modules\Content\Repositories\Interfaces\EntryRepositoryInterface;
use App\Modules\Content\Validators\EntryValidator;
use App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface;
use App\Modules\System\Authorization\Services\AuthorizationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Modules\Content\Revisions\Services\Interfaces\RevisionServiceInterface;
use App\Modules\System\Realtime\Events\EntryUpdatedRealtimeEvent;


final class EntryService implements EntryServiceInterface
{
    public function __construct(
        private readonly CollectionRepositoryInterface $collections,
        private readonly EntryRepositoryInterface $entries,
        private readonly AuthorizationService $authz,
        private readonly AuditLogServiceInterface $audit,
        private readonly \App\Modules\Content\Revisions\Services\Interfaces\RevisionServiceInterface $revisions

    ) {
    }

    public function list(string $collectionHandle, array $query): LengthAwarePaginator
    {
        $spaceId = $this->requireSpaceId();

        $collection = $this->resolveCollection($collectionHandle, $spaceId);

        $this->authz->requirePermission("{$collectionHandle}.read");

        $perPage = (int) ($query['per_page'] ?? 20);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $status = (string) ($query['status'] ?? 'all');
        $allowedStatuses = ['draft', 'published', 'scheduled', 'archived', 'all'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $search = isset($query['search']) ? (string) $query['search'] : null;
        if ($search !== null && mb_strlen($search) > 255) {
            $search = mb_substr($search, 0, 255);
        }

        $sort = (string) ($query['sort'] ?? '-id');
        $filter = $query['filter'] ?? [];
        if (!is_array($filter)) {
            $filter = [];
        }

        $filters = [
            'status' => $status,
            'search' => $search,
            'sort' => $sort,
            'filter' => $filter,
        ];

        $paginator = $this->entries->listFiltered($spaceId, $collection->id, $filters, $perPage);
        $this->maskPasswordFieldsInPaginator($paginator, $collection);
        return $paginator;
    }

    public function create(string $collectionHandle, array $input): Entry
    {
        $spaceId = $this->requireSpaceId();

        $collection = $this->resolveCollection($collectionHandle, $spaceId);

        $this->authz->requirePermission("{$collectionHandle}.create");

        $normalized = $this->normalizeInputToData($input);
        $validated = EntryValidator::validateUpsert($collection, $normalized);

        $status = $validated['status'] ?? 'draft';
        $publishedAt = $validated['published_at'] ?? null;

        if ($status === 'published' && !$publishedAt) {
            $publishedAt = Carbon::now();
        }

        $data = $this->prepareDataForSave($collection, $validated['data'] ?? []);

        DB::beginTransaction();
        try {
            $entry = $this->entries->create([
                'space_id' => $spaceId,
                'collection_id' => $collection->id,
                'status' => $status,
                'published_at' => $publishedAt,
                'data' => $data,
            ]);

            $this->audit->write(
                action: 'entry.create',
                resource: $collectionHandle,
                diff: ['after' => $entry->toArray()],
                spaceId: $spaceId,
                actorId: auth()->id()
            );

            DB::commit();

            Log::info('Entry created', [
                'space_id' => $spaceId,
                'collection_id' => $collection->id,
                'entry_id' => $entry->id,
                'handle' => $collectionHandle,
                'user_id' => auth()->id(),
            ]);

            return $entry;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Entry create failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(string $collectionHandle, int $id, array $query): array
    {
        $spaceId = $this->requireSpaceId();

        $collection = $this->resolveCollection($collectionHandle, $spaceId);

        $this->authz->requirePermission("{$collectionHandle}.read");

        $entry = $this->entries->findOrFail($spaceId, $collection->id, $id);

        $payload = $entry->toArray();
        $payload['data'] = $this->maskPasswordFields($payload['data'] ?? [], $collection);

        if (!empty($query['fields'])) {
            $payload = $this->projectFields($payload, (string) $query['fields']);
        }

        $populate = (string) ($query['populate'] ?? '0');
        if ($populate === '1' || $populate === 'true') {
            $maxDepth = (int) ($query['max_depth'] ?? 2);
            if ($maxDepth < 0) $maxDepth = 0;
            if ($maxDepth > 8) $maxDepth = 8;

            $payload['data'] = $this->limitDepth($payload['data'] ?? [], $maxDepth);
        }

        return $payload;
    }

public function update(string $collectionHandle, int $id, array $input): Entry
{
    $spaceId = $this->requireSpaceId();

    $collection = $this->resolveCollection($collectionHandle, $spaceId);

    $this->authz->requirePermission("{$collectionHandle}.update");

    $entry = $this->entries->findOrFail($spaceId, $collection->id, $id);

    $beforeSnapshot = [
        'data' => (array) $entry->data,
        'status' => $entry->status,
        'published_at' => $entry->published_at,
    ];

    $normalized = $this->normalizeInputToData($input);
    $validated = EntryValidator::validateUpsert($collection, $normalized);

    $status = $validated['status'] ?? $entry->status;
    $publishedAt = array_key_exists('published_at', $validated) ? $validated['published_at'] : $entry->published_at;

    if ($status === 'published' && !$publishedAt) {
        $publishedAt = Carbon::now();
    }

    $data = $this->prepareDataForSave($collection, $validated['data'] ?? $entry->data, $entry);

    DB::beginTransaction();
    try {
        $updated = $this->entries->update($entry, [
            'status' => $status,
            'published_at' => $publishedAt,
            'data' => $data,
        ]);

        $afterSnapshot = [
            'data' => (array) $updated->data,
            'status' => $updated->status,
            'published_at' => $updated->published_at,
        ];

        $this->revisions->createOnUpdate(
            $collectionHandle,
            (int) $updated->id,
            $beforeSnapshot,
            $afterSnapshot
        );

        $this->audit->write(
            action: 'entry.update',
            resource: $collectionHandle,
            diff: ['before' => $beforeSnapshot, 'after' => $afterSnapshot],
            spaceId: $spaceId,
            actorId: auth()->id()
        );

        DB::commit();

        broadcast(new EntryUpdatedRealtimeEvent($spaceId, $collectionHandle, (int) $updated->id));

        Log::info('Entry updated', [
            'space_id' => $spaceId,
            'collection_id' => $collection->id,
            'entry_id' => $updated->id,
            'handle' => $collectionHandle,
            'user_id' => auth()->id(),
        ]);

        return $updated;
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Entry update failed', ['message' => $e->getMessage()]);
        throw $e;
    }
}


    public function delete(string $collectionHandle, int $id): void
    {
        $spaceId = $this->requireSpaceId();

        $collection = $this->resolveCollection($collectionHandle, $spaceId);

        $this->authz->requirePermission("{$collectionHandle}.delete");

        $entry = $this->entries->findOrFail($spaceId, $collection->id, $id);
        $before = $entry->toArray();

        DB::beginTransaction();
        try {
            $this->entries->delete($entry);

            $this->audit->write(
                action: 'entry.delete',
                resource: $collectionHandle,
                diff: ['before' => $before],
                spaceId: $spaceId,
                actorId: auth()->id()
            );

            DB::commit();

            Log::info('Entry deleted', [
                'space_id' => $spaceId,
                'collection_id' => $collection->id,
                'entry_id' => $id,
                'handle' => $collectionHandle,
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Entry delete failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    private function resolveCollection(string $handle, int $spaceId): Collection
    {
        return \App\Models\Collection::query()
            ->where('space_id', $spaceId)
            ->where('handle', $handle)
            ->firstOrFail();
    }

    private function requireSpaceId(): int
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            abort(422, 'Missing space context (X-Space-Id)');
        }
        return $spaceId;
    }

    private function normalizeInputToData(array $input): array
    {
        if (isset($input['data']) && is_array($input['data'])) {
            return $input;
        }

        $data = $input;
        unset($data['status'], $data['published_at']);

        return [
            'status' => $input['status'] ?? null,
            'published_at' => $input['published_at'] ?? null,
            'data' => $data,
        ];
    }

    private function projectFields(array $entry, string $fieldsCsv): array
    {
        $wanted = array_values(array_filter(array_map('trim', explode(',', $fieldsCsv))));
        if (empty($wanted)) return $entry;

        $data = $entry['data'] ?? [];
        $projected = [];
        foreach ($wanted as $k) {
            if (array_key_exists($k, $data)) {
                $projected[$k] = $data[$k];
            }
        }

        $entry['data'] = $projected;
        return $entry;
    }

    private function limitDepth(mixed $value, int $maxDepth, int $depth = 0): mixed
    {
        if ($depth >= $maxDepth) {
            if (is_array($value)) return []; // prune
            return $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = $this->limitDepth($v, $maxDepth, $depth + 1);
        }
        return $out;
    }

    private function prepareDataForSave(Collection $collection, array $data, ?Entry $entry = null): array
    {
        $fields = $collection->fields ?? [];
        $existingData = $entry ? (array) $entry->data : [];
        foreach ($fields as $field) {
            $handle = $field['handle'] ?? null;
            $type = $field['type'] ?? 'text';
            $localized = (bool) ($field['localized'] ?? false);
            if (!$handle) {
                continue;
            }

            if ($localized) {
                $value = $data[$handle] ?? null;
                if (!is_array($value)) {
                    $value = $value !== null ? [config('content.supported_locales', ['en'])[0] ?? 'en' => $value] : [];
                }
                if ($entry && isset($existingData[$handle]) && is_array($existingData[$handle])) {
                    $data[$handle] = array_merge($existingData[$handle], $value);
                } else {
                    $data[$handle] = $value;
                }
                continue;
            }

            if ($type === 'password') {
                $value = $data[$handle] ?? null;
                if ($value !== null && $value !== '') {
                    $data[$handle] = Hash::make($value);
                } elseif ($entry && array_key_exists($handle, $existingData)) {
                    $data[$handle] = $existingData[$handle]; // keep existing hash on update
                }
                continue;
            }

            if ($type === 'slug' && ($data[$handle] ?? '') === '' && !empty($field['source_field'])) {
                $source = $data[$field['source_field']] ?? null;
                if ($source !== null && $source !== '') {
                    $data[$handle] = $this->slugFromString((string) $source);
                }
            }
        }
        return $data;
    }

    public function slugFromString(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9\s-]/', '', $value);
        $value = preg_replace('/[\s-]+/', '-', $value);
        return strtolower(trim($value, '-'));
    }

    private function maskPasswordFields(array $data, Collection $collection): array
    {
        $fields = $collection->fields ?? [];
        foreach ($fields as $field) {
            $handle = $field['handle'] ?? null;
            if ($handle && ($field['type'] ?? '') === 'password' && array_key_exists($handle, $data)) {
                $data[$handle] = null;
            }
        }
        return $data;
    }

    private function maskPasswordFieldsInPaginator(LengthAwarePaginator $paginator, Collection $collection): void
    {
        $paginator->getCollection()->transform(function (Entry $item) use ($collection) {
            $item->setAttribute('data', $this->maskPasswordFields($item->data ?? [], $collection));
            return $item;
        });
    }
}
