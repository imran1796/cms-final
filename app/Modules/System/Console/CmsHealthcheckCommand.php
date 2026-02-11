<?php

namespace App\Modules\System\Console;

use App\Modules\System\System\Services\Interfaces\SystemServiceInterface;
use Illuminate\Console\Command;

final class CmsHealthcheckCommand extends Command
{
    protected $signature = 'cms:healthcheck {--json : Output JSON}';
    protected $description = 'CMS healthcheck (DB, cache, queue). Same checks as GET /api/v1/system/health.';

    public function __construct(
        private readonly SystemServiceInterface $system
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $data = $this->system->health();
        $ok = $data['ok'];

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok' => $ok,
                'checks' => [
                    'db' => $data['db'],
                    'cache' => $data['cache'],
                    'queue' => $data['queue'],
                ],
            ], JSON_PRETTY_PRINT));
        } else {
            $this->info('CMS Healthcheck');
            foreach (['db', 'cache', 'queue'] as $k) {
                $v = $data[$k];
                $label = strtoupper($k);
                $status = $v['ok'] ? 'OK' : 'FAIL';
                $detail = $v['message'] ?? $v['error'] ?? '-';
                $this->line("{$label}: {$status} - {$detail}");
            }
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
