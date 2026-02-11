<?php

namespace App\Modules\System\Settings\Services;

use App\Models\Setting;
use App\Modules\System\Authorization\Services\AuthorizationService;
use Illuminate\Support\Facades\DB;

final class SettingsService
{
    public function __construct(
        private readonly AuthorizationService $authz
    ) {
    }

    public function getAll(): array
    {
        $this->authz->requirePermission('manage_settings');

        $rows = Setting::query()->orderBy('key')->get();
        $out = [];
        foreach ($rows as $row) {
            $out[$row->key] = $this->decodeValue($row->value);
        }
        return $out;
    }

    public function update(array $settings): array
    {
        $this->authz->requirePermission('manage_settings');

        $allowedKeys = config('settings.allowed_keys');
        if (is_array($allowedKeys)) {
            $settings = array_intersect_key($settings, array_flip($allowedKeys));
        }

        DB::transaction(function () use ($settings) {
            foreach ($settings as $key => $value) {
                if (!is_string($key) || $key === '') {
                    continue;
                }
                if (strlen($key) > 191) {
                    continue;
                }
                $encoded = $this->encodeValue($value);
                Setting::query()->updateOrInsert(
                    ['key' => $key],
                    ['value' => $encoded, 'updated_at' => now()]
                );
            }
        });

        return $this->getAll();
    }

    private function decodeValue(?string $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        return $value;
    }

    private function encodeValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return (string) $value;
    }
}
