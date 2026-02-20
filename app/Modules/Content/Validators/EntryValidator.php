<?php

namespace App\Modules\Content\Validators;

use App\Models\Collection;
use App\Modules\Content\Services\ContentLocaleConfigService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class EntryValidator
{
    public static function validateUpsert(Collection $collection, array $input): array
    {
        $baseValidator = Validator::make($input, [
            'status' => ['sometimes', 'string', Rule::in(['draft', 'scheduled', 'published', 'archived'])],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'unpublish_at' => ['sometimes', 'nullable', 'date'],
            'data' => ['sometimes', 'array'],
        ]);
        $baseValidator->after(function (\Illuminate\Validation\Validator $v) use ($input): void {
            if (!array_key_exists('published_at', $input) || !array_key_exists('unpublish_at', $input)) {
                return;
            }
            $publishedAt = $input['published_at'];
            $unpublishAt = $input['unpublish_at'];
            if ($publishedAt === null || $unpublishAt === null) {
                return;
            }
            $publishedTs = strtotime((string) $publishedAt);
            $unpublishTs = strtotime((string) $unpublishAt);
            if ($publishedTs !== false && $unpublishTs !== false && $unpublishTs <= $publishedTs) {
                $v->errors()->add('unpublish_at', 'unpublish_at must be after published_at');
            }
        });
        $base = $baseValidator->validate();

        $schemaFields = $collection->fields ?? [];
        $data = $base['data'] ?? [];

        $rules = self::buildRulesForFields($schemaFields, 'data.', $collection);
        $validator = Validator::make($base, $rules);
        $validator->after(function (\Illuminate\Validation\Validator $v) use ($base, $schemaFields, $collection): void {
            self::validateLayoutFields($v, $base, $schemaFields, $collection);
            self::validateLocalizedFieldKeys($v, $base, $schemaFields);
        });
        $validator->validate();

        return $base;
    }

    private static function validateLocalizedFieldKeys(\Illuminate\Validation\Validator $validator, array $base, array $schemaFields): void
    {
        $data = $base['data'] ?? [];
        $supportedLocales = self::resolvedSupportedLocales();
        foreach ($schemaFields as $field) {
            if (!($field['localized'] ?? false)) {
                continue;
            }
            $handle = $field['handle'] ?? null;
            if (!$handle || !is_string($handle)) {
                continue;
            }
            $value = $data[$handle] ?? null;
            if (!is_array($value)) {
                continue;
            }
            foreach (array_keys($value) as $localeKey) {
                if (!in_array($localeKey, $supportedLocales, true)) {
                    $validator->errors()->add("data.{$handle}", "Locale \"{$localeKey}\" is not in supported locales: " . implode(', ', $supportedLocales));
                    break;
                }
            }
        }
    }

    private static function validateLayoutFields(\Illuminate\Validation\Validator $validator, array $base, array $schemaFields, Collection $collection): void
    {
        $data = $base['data'] ?? [];
        foreach ($schemaFields as $field) {
            if (($field['type'] ?? '') !== 'layout') {
                continue;
            }
            $handle = $field['handle'] ?? null;
            if (!$handle || !is_string($handle)) {
                continue;
            }
            $blocks = $field['blocks'] ?? [];
            $items = $data[$handle] ?? [];
            if (!is_array($items)) {
                return;
            }
            foreach ($items as $i => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $blockType = $item['block_type'] ?? null;
                $block = null;
                foreach ($blocks as $b) {
                    if (($b['type'] ?? '') === $blockType) {
                        $block = $b;
                        break;
                    }
                }
                if (!$block) {
                    continue;
                }
                $blockData = $item['data'] ?? [];
                $nestedRules = self::buildRulesForFields($block['fields'], '', $collection);
                $nestedValidator = Validator::make($blockData, $nestedRules);
                if ($nestedValidator->fails()) {
                    foreach ($nestedValidator->errors()->messages() as $nestedKey => $msgs) {
                        $validator->errors()->add("data.{$handle}.{$i}.data.{$nestedKey}", $msgs[0]);
                    }
                }
            }
        }
    }

    private static function buildRulesForFields(array $schemaFields, string $prefix, ?Collection $collection = null): array
    {
        $rules = [];
        $supportedLocales = self::resolvedSupportedLocales();

        foreach ($schemaFields as $field) {
            $handle = $field['handle'] ?? null;
            if (!$handle || !is_string($handle)) {
                continue;
            }

            $required = (bool) ($field['required'] ?? false);
            $type = $field['type'] ?? 'text';
            $localized = (bool) ($field['localized'] ?? false);
            $key = $prefix . $handle;

            if ($localized) {
                $rules[$key] = array_merge(
                    $required ? ['required'] : ['sometimes', 'nullable'],
                    ['array']
                );
                foreach ($supportedLocales as $locale) {
                    $localeKey = $key . '.' . $locale;
                    $typeRules = self::rulesForType($type, $field);
                    $rules[$localeKey] = array_merge(['sometimes', 'nullable'], $typeRules);
                    if ($type === 'tags') {
                        $values = self::getOptionValues($field);
                        $rules[$localeKey . '.*'] = $values ? [Rule::in($values)] : ['string'];
                    }
                }
                continue;
            }

            if ($type === 'repeater') {
                $rules[$key] = array_merge(
                    $required ? ['required'] : ['sometimes', 'nullable'],
                    ['array']
                );
                $nestedFields = $field['fields'] ?? [];
                $nestedRules = self::buildRulesForFields($nestedFields, $key . '.*.', $collection);
                $rules = array_merge($rules, $nestedRules);
            } elseif ($type === 'layout') {
                $blocks = $field['blocks'] ?? [];
                $blockTypes = array_column($blocks, 'type');
                $rules[$key] = array_merge(
                    $required ? ['required'] : ['sometimes', 'nullable'],
                    ['array']
                );
                $rules[$key . '.*.block_type'] = ['required', 'string', Rule::in($blockTypes)];
                $rules[$key . '.*.data'] = ['required', 'array'];
            } elseif (in_array($type, ['asset', 'assets'], true)) {
                $spaceId = $collection ? $collection->space_id : null;
                $existsRule = $spaceId !== null ? Rule::exists('media', 'id')->where('space_id', $spaceId) : null;
                if ($existsRule && !empty($field['allowed_kinds'])) {
                    $existsRule = $existsRule->whereIn('kind', $field['allowed_kinds']);
                }
                if ($type === 'asset') {
                    $rules[$key] = array_merge(
                        $required ? ['required'] : ['sometimes', 'nullable'],
                        ['integer'],
                        $existsRule ? [$existsRule] : []
                    );
                } else {
                    $rules[$key] = array_merge(
                        $required ? ['required'] : ['sometimes', 'nullable'],
                        ['array']
                    );
                    $rules[$key . '.*'] = array_merge(['integer'], $existsRule ? [$existsRule] : []);
                }
            } elseif ($type === 'relation') {
                $rel = $field['relation'] ?? [];
                $refHandle = (string) ($rel['collection'] ?? '');
                $max = $rel['max'] ?? null;
                $refCollId = $collection && $refHandle !== '' ? self::resolveCollectionId($collection->space_id, $refHandle) : 0;
                $existsRule = $refCollId > 0
                    ? Rule::exists('entries', 'id')->where('space_id', $collection->space_id)->where('collection_id', $refCollId)
                    : null;
                if ($max === 1) {
                    $rules[$key] = array_merge(
                        $required ? ['required'] : ['sometimes', 'nullable'],
                        ['integer'],
                        $existsRule ? [$existsRule] : []
                    );
                } else {
                    $rules[$key] = array_merge(
                        $required ? ['required'] : ['sometimes', 'nullable'],
                        ['array']
                    );
                    $rules[$key . '.*'] = array_merge(['integer'], $existsRule ? [$existsRule] : []);
                }
            } else {
                $rules[$key] = array_merge(
                    $required ? ['required'] : ['sometimes', 'nullable'],
                    self::rulesForType($type, $field)
                );
                if ($type === 'tags') {
                    $values = self::getOptionValues($field);
                    $rules[$key . '.*'] = $values ? [Rule::in($values)] : ['string'];
                }
            }
        }
        return $rules;
    }

    private static function resolveCollectionId(int $spaceId, string $handle): int
    {
        $coll = Collection::query()->where('space_id', $spaceId)->where('handle', $handle)->first();

        return $coll ? (int) $coll->id : 0;
    }

    private static function rulesForType(string $type, array $field): array
    {
        $choiceValues = self::getOptionValues($field);
        return match ($type) {
            'text', 'textarea', 'richtext' => ['string'],
            'number' => ['numeric'],
            'boolean' => ['boolean'],
            'date' => ['date'],
            'datetime' => ['date'],
            'time' => ['date_format:H:i'],
            'json' => ['array'],
            'select', 'radio', 'enum' => $choiceValues ? ['string', Rule::in($choiceValues)] : ['string'],
            'tags' => ['array'],
            'slug' => ['string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'password' => ['string', 'min:8'],
            'color' => $choiceValues ? ['string', Rule::in($choiceValues)] : ['string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            default => ['string'],
        };
    }

    private static function getOptionValues(array $field): array
    {
        $opts = $field['options'] ?? [];
        if (isset($opts['values']) && is_array($opts['values'])) {
            return array_values(array_map('strval', $opts['values']));
        }
        if (is_array($opts) && array_is_list($opts)) {
            return array_values(array_map('strval', $opts));
        }
        return [];
    }

    private static function resolvedSupportedLocales(): array
    {
        $cfg = self::resolvedLocaleConfig();
        $locales = $cfg['supported_locales'] ?? ['en'];
        return is_array($locales) && $locales !== [] ? array_values($locales) : ['en'];
    }

    private static function resolvedLocaleConfig(): array
    {
        try {
            /** @var ContentLocaleConfigService $service */
            $service = app(ContentLocaleConfigService::class);
            return $service->get();
        } catch (\Throwable) {
            $fallbackLocales = config('content.supported_locales', ['en']);
            if (!is_array($fallbackLocales) || $fallbackLocales === []) {
                $fallbackLocales = ['en'];
            }
            $fallbackDefault = (string) (config('content.default_locale', $fallbackLocales[0] ?? 'en'));
            if ($fallbackDefault === '') {
                $fallbackDefault = $fallbackLocales[0] ?? 'en';
            }
            return [
                'supported_locales' => $fallbackLocales,
                'default_locale' => $fallbackDefault,
            ];
        }
    }
}
