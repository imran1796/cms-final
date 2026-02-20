<?php

namespace App\Modules\Content\Services;

use App\Models\Setting;

final class ContentLocaleConfigService
{
    private ?array $cached = null;

    public function get(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $fallbackLocales = $this->normalizeLocales(config('content.supported_locales', ['en']));
        if ($fallbackLocales === []) {
            $fallbackLocales = ['en'];
        }

        $fallbackDefault = $this->normalizeLocale(config('content.default_locale'));
        if ($fallbackDefault === null) {
            $fallbackDefault = $fallbackLocales[0];
        }

        $supportedLocales = $fallbackLocales;
        $defaultLocale = $fallbackDefault;

        try {
            $supportedRow = Setting::query()->where('key', 'content_supported_locales')->value('value');
            $defaultRow = Setting::query()->where('key', 'content_default_locale')->value('value');

            $dbLocales = $this->normalizeLocales($this->decodeSettingValue($supportedRow));
            if ($dbLocales !== []) {
                $supportedLocales = $dbLocales;
            }

            $dbDefault = $this->normalizeLocale($this->decodeSettingValue($defaultRow));
            if ($dbDefault !== null) {
                $defaultLocale = $dbDefault;
            }
        } catch (\Throwable) {
            // Keep fallback config values if settings table/data is unavailable.
        }

        if (!in_array($defaultLocale, $supportedLocales, true)) {
            array_unshift($supportedLocales, $defaultLocale);
            $supportedLocales = $this->normalizeLocales($supportedLocales);
        }

        $this->cached = [
            'supported_locales' => $supportedLocales,
            'default_locale' => $defaultLocale,
        ];

        return $this->cached;
    }

    private function decodeSettingValue(mixed $raw): mixed
    {
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $raw;
    }

    private function normalizeLocales(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $locale) {
            $normalized = $this->normalizeLocale($locale);
            if ($normalized !== null) {
                $out[] = $normalized;
            }
        }

        return array_values(array_unique($out));
    }

    private function normalizeLocale(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $locale = trim((string) $value);
        if ($locale === '') {
            return null;
        }

        return $locale;
    }
}

