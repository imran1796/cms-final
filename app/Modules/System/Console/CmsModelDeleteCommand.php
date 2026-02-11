<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class CmsModelDeleteCommand extends Command
{
    protected $signature = 'cms:model:delete {space : Space id or handle} {handle : Collection handle} {--force : Skip confirmation}';
    protected $description = 'Delete a Content Model (collection)';

    public function handle(): int
    {
        $space = (string) $this->argument('space');
        $handle = (string) $this->argument('handle');

        if (!$this->option('force')) {
            if (!$this->confirm("Delete model '{$handle}'? This deletes the collection definition.")) {
                $this->line('Cancelled');
                return self::SUCCESS;
            }
        }

        try {
            $spaceId = $this->resolveSpaceId($space);

            DB::beginTransaction();

            $collection = DB::table('collections')
                ->where('space_id', $spaceId)
                ->where('handle', $handle)
                ->first();

            if (!$collection) {
                $this->error('Collection not found');
                DB::rollBack();
                return self::FAILURE;
            }

            DB::table('collections')->where('id', (int) $collection->id)->delete();

            $perms = [
                "{$handle}.create",
                "{$handle}.read",
                "{$handle}.update",
                "{$handle}.delete",
                "{$handle}.publish",
            ];
            Permission::whereIn('name', $perms)->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();

            Log::info('CLI model deleted', [
                'space_id' => $spaceId,
                'handle' => $handle,
                'collection_id' => (int) $collection->id,
            ]);

            $this->info("Deleted model '{$handle}' from space {$spaceId}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Delete failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    private function resolveSpaceId(string $space): int
    {
        if (ctype_digit($space)) {
            return (int) $space;
        }
        $row = DB::table('spaces')->where('handle', $space)->first();
        return $row ? (int) $row->id : 0;
    }
}
