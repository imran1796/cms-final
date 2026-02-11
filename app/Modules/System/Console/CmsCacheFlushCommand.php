<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class CmsCacheFlushCommand extends Command
{
    protected $signature = 'cms:cache:flush {--tags=* : Cache tags to flush (optional)}';
    protected $description = 'Flush app cache (and optionally tag caches)';

    public function handle(): int
    {
        $tags = (array) $this->option('tags');

        try {
            Cache::flush();

            if (!empty($tags)) {
                try {
                    Cache::tags($tags)->flush();
                } catch (\Throwable $e) {
                }
            }

            $this->info('Cache flushed');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Cache flush failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
