<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CmsAssetsCleanupCommand extends Command
{
    protected $signature = 'cms:assets:cleanup {--dry-run : Do not delete, only report}';
    protected $description = 'Cleanup orphan media_variants rows (minimal)';

    public function handle(): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('media_variants') || !DB::getSchemaBuilder()->hasTable('media')) {
                $this->warn('media/media_variants tables not found; skipping');
                return self::SUCCESS;
            }

            $dry = (bool) $this->option('dry-run');

            $orphans = DB::table('media_variants')
                ->leftJoin('media', 'media.id', '=', 'media_variants.media_id')
                ->whereNull('media.id')
                ->select('media_variants.id')
                ->get();

            $count = $orphans->count();

            if ($count === 0) {
                $this->info('No orphan variants found');
                return self::SUCCESS;
            }

            if ($dry) {
                $this->info("Dry run: would delete {$count} orphan variants");
                return self::SUCCESS;
            }

            DB::table('media_variants')->whereIn('id', $orphans->pluck('id')->all())->delete();

            $this->info("Deleted {$count} orphan variants");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Cleanup failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
