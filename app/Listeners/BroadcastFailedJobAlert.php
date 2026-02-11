<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use App\Modules\System\Realtime\Events\SystemAlertRealtimeEvent;
use Illuminate\Support\Facades\Log;

final class BroadcastFailedJobAlert
{
    public function handle(JobFailed $event): void
    {
        try {
            $jobName = method_exists($event->job, 'resolveName')
                ? $event->job->resolveName()
                : (method_exists($event->job, 'getName') ? $event->job->getName() : 'unknown');
            $exception = $event->exception;
            $message = $exception ? $exception->getMessage() : 'Job failed';
            $fullMessage = $jobName . ': ' . $message;
            if (mb_strlen($fullMessage) > 500) {
                $fullMessage = mb_substr($fullMessage, 0, 497) . '...';
            }

            $link = null;
            if (class_exists(\Laravel\Horizon\Horizon::class)) {
                $link = rtrim(config('app.url', ''), '/') . '/horizon/failed';
            }

            broadcast(new SystemAlertRealtimeEvent('failed_job', $fullMessage, $link));
        } catch (\Throwable $e) {
            Log::warning('BroadcastFailedJobAlert failed', ['message' => $e->getMessage()]);
        }
    }
}
