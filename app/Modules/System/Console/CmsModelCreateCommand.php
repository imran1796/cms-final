<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\Exceptions\NotFoundApiException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class CmsModelCreateCommand extends Command
{
    protected $signature = 'cms:model:create
        {space : Space id or handle}
        {handle : Collection handle (e.g. posts)}
        {--type=collection : collection|singleton|tree}
        {--title= : Optional title}
    ';

    protected $description = 'Create a Content Model (collection)';

    public function handle(): int
    {
        $space = (string) $this->argument('space');
        $handle = (string) $this->argument('handle');
        $type = (string) $this->option('type');
        $title = (string) ($this->option('title') ?: $handle);

        if (!in_array($type, ['collection', 'singleton', 'tree'], true)) {
            $this->error('Invalid type. Use: collection|singleton|tree');
            return self::FAILURE;
        }

        try {
            $spaceId = $this->resolveSpaceId($space);

            $exists = DB::table('collections')
                ->where('space_id', $spaceId)
                ->where('handle', $handle)
                ->exists();

            if ($exists) {
                $this->error("Collection '{$handle}' already exists in space {$spaceId}");
                return self::FAILURE;
            }

            DB::beginTransaction();

            $id = DB::table('collections')->insertGetId([
                'space_id' => $spaceId,
                'handle' => $handle,
                'type' => $type,
                'fields' => json_encode([]),
                'settings' => json_encode(['title' => $title]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $perms = [
                "{$handle}.create",
                "{$handle}.read",
                "{$handle}.update",
                "{$handle}.delete",
                "{$handle}.publish",
            ];

            foreach ($perms as $p) {
                Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();

            Log::info('CLI model created', [
                'space_id' => $spaceId,
                'handle' => $handle,
                'type' => $type,
                'collection_id' => $id,
            ]);

            $this->info("Created model '{$handle}' in space {$spaceId} (id={$id})");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Create failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    private function resolveSpaceId(string $space): int
    {
        if (ctype_digit($space)) {
            $id = (int) $space;
            $exists = DB::table('spaces')->where('id', $id)->exists();
            if (!$exists) {
                throw new NotFoundApiException('Space not found');
            }
            return $id;
        }

        $row = DB::table('spaces')->where('handle', $space)->first();
        if (!$row) {
            throw new NotFoundApiException('Space not found');
        }
        return (int) $row->id;
    }
}
