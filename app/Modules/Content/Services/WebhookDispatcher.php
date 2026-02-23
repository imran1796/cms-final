<?php

namespace App\Modules\Content\Services;

use App\Models\Entry;
use App\Modules\Content\Jobs\DispatchWebhookJob;
use App\Modules\Content\Contracts\WebhookDispatcherInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WebhookDispatcher implements WebhookDispatcherInterface
{
    public function dispatchEntryPublished(int $spaceId, string $collectionHandle, Entry $entry): void
    {
        $urls = $this->getWebhookUrls($spaceId, $collectionHandle);
        if (empty($urls)) {
            return;
        }

        $payload = $this->buildPayload($spaceId, $collectionHandle, $entry);
        $secret = config('cms_webhooks.secret');

        foreach ($urls as $url) {
            try {
                $this->sendWebhook($url, $payload, $secret);
            } catch (\Throwable $e) {
                Log::warning('Webhook dispatch failed', [
                    'url' => $url,
                    'space_id' => $spaceId,
                    'collection' => $collectionHandle,
                    'entry_id' => $entry->id,
                    'message' => $e->getMessage(),
                ]);

                if (config('cms_webhooks.retry_on_failure', false)) {
                    $this->queueWebhook($url, $payload, $secret);
                }
            }
        }
    }

    private function getWebhookUrls(int $spaceId, string $collectionHandle): array
    {
        $urls = [];

        $global = config('cms_webhooks.global_urls', []);
        if (is_string($global)) {
            $global = array_filter(explode(',', $global));
        }
        $urls = array_merge($urls, $global);

        $spaceUrls = config("cms_webhooks.spaces.{$spaceId}", []);
        if (is_string($spaceUrls)) {
            $spaceUrls = array_filter(explode(',', $spaceUrls));
        }
        $urls = array_merge($urls, $spaceUrls);

        $collectionUrls = config("cms_webhooks.collections.{$spaceId}.{$collectionHandle}", []);
        if (is_string($collectionUrls)) {
            $collectionUrls = array_filter(explode(',', $collectionUrls));
        }
        $urls = array_merge($urls, $collectionUrls);

        return array_unique(array_filter($urls));
    }

    private function buildPayload(int $spaceId, string $collectionHandle, Entry $entry): array
    {
        return [
            'event' => 'entry.published',
            'timestamp' => now()->toIso8601String(),
            'space_id' => $spaceId,
            'collection' => $collectionHandle,
            'entry' => [
                'id' => $entry->id,
                'status' => $entry->status,
                'published_at' => $entry->published_at?->toIso8601String(),
                'title' => $entry->title,
                'slug' => $entry->slug,
                'data' => $entry->data,
                'created_at' => $entry->created_at?->toIso8601String(),
                'updated_at' => $entry->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function sendWebhook(string $url, array $payload, ?string $secret): void
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $idempotencyKey = $this->buildIdempotencyKey($url, $payload);
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Laravel-CMS/1.0',
            'X-Webhook-Idempotency-Key' => $idempotencyKey,
        ];

        if ($secret) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', $body, $secret);
        }

        $timeout = (int) config('cms_webhooks.timeout_seconds', 10);

        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->withBody($body, 'application/json')
            ->post($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Webhook returned status {$response->status()}");
        }

        Log::info('Webhook dispatched', [
            'url' => $url,
            'status' => $response->status(),
        ]);
    }

    private function queueWebhook(string $url, array $payload, ?string $secret): void
    {
        $idempotencyKey = $this->buildIdempotencyKey($url, $payload);
        DispatchWebhookJob::dispatch($url, $payload, $secret, $idempotencyKey);
        Log::info('Webhook queued for retry', [
            'url' => $url,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    private function buildIdempotencyKey(string $url, array $payload): string
    {
        $raw = $url . '|' . json_encode($payload, JSON_UNESCAPED_SLASHES);
        return hash('sha256', $raw ?: $url);
    }
}
