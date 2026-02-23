<?php

namespace App\Modules\Content\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public array $backoff;

    public function __construct(
        public readonly string $url,
        public readonly array $payload,
        public readonly ?string $secret,
        public readonly string $idempotencyKey,
    ) {
        $this->tries = max(1, (int) config('cms_webhooks.retry_tries', 3));
        $this->backoff = $this->normalizeBackoff(config('cms_webhooks.retry_backoff_seconds', [5, 15, 60]));
        $this->onQueue((string) config('cms_webhooks.retry_queue', 'default'));
    }

    public function handle(): void
    {
        $sentKey = 'webhook:sent:' . $this->idempotencyKey;
        if (Cache::has($sentKey)) {
            return;
        }

        $body = json_encode($this->payload, JSON_UNESCAPED_SLASHES) ?: '{}';
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Laravel-CMS/1.0',
            'X-Webhook-Idempotency-Key' => $this->idempotencyKey,
        ];

        if ($this->secret) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', $body, $this->secret);
        }

        $timeout = (int) config('cms_webhooks.timeout_seconds', 10);

        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->withBody($body, 'application/json')
            ->post($this->url);

        if (!$response->successful()) {
            throw new \RuntimeException("Webhook retry returned status {$response->status()}");
        }

        $ttlSeconds = max(60, (int) config('cms_webhooks.idempotency_ttl_seconds', 86400));
        Cache::put($sentKey, 1, now()->addSeconds($ttlSeconds));

        Log::info('Webhook retry dispatched', [
            'url' => $this->url,
            'status' => $response->status(),
            'idempotency_key' => $this->idempotencyKey,
            'attempt' => $this->attempts(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Webhook retry exhausted', [
            'url' => $this->url,
            'idempotency_key' => $this->idempotencyKey,
            'message' => $e->getMessage(),
        ]);
    }

    private function normalizeBackoff(mixed $value): array
    {
        if (is_string($value)) {
            $parts = array_map('trim', explode(',', $value));
            $items = array_values(array_filter(array_map(static fn($v) => is_numeric($v) ? (int) $v : null, $parts)));
            return $items !== [] ? $items : [5, 15, 60];
        }

        if (is_array($value)) {
            $items = array_values(array_filter(array_map(static fn($v) => is_numeric($v) ? (int) $v : null, $value)));
            return $items !== [] ? $items : [5, 15, 60];
        }

        return [5, 15, 60];
    }
}
