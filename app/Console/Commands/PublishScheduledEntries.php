<?php

namespace App\Console\Commands;

use App\Modules\Content\Services\PublishingServiceInterface;
use Illuminate\Console\Command;

final class PublishScheduledEntries extends Command
{
    protected $signature = 'cms:publish-scheduled';
    protected $description = 'Publish scheduled entries (scheduled with published_at <= now)';

    public function handle(PublishingServiceInterface $publishing): int
    {
        $count = $publishing->publishScheduled();
        $this->info("Published {$count} scheduled entries.");
        return self::SUCCESS;
    }
}
