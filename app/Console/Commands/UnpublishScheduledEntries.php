<?php

namespace App\Console\Commands;

use App\Modules\Content\Services\PublishingServiceInterface;
use Illuminate\Console\Command;

final class UnpublishScheduledEntries extends Command
{
    protected $signature = 'cms:unpublish-scheduled';
    protected $description = 'Unpublish entries when unpublish_at <= now';

    public function handle(PublishingServiceInterface $publishing): int
    {
        $count = $publishing->unpublishScheduled();
        $this->info("Unpublished {$count} entries.");
        return self::SUCCESS;
    }
}
