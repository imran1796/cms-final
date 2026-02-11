<?php

namespace App\Modules\Content\Validators;

use App\Models\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class EntryValidator
{
    public static function validateUpsert(Collection $collection, array $input): array
    {
        $base = Validator::make($input, [
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['sometimes', 'date'],
            'data' => ['sometimes', 'array'],
        ])->validate();

        $schemaFields = $collection->fields ?? [];
        $data = $base['data'] ?? [];

        $rules = self::buildRulesForFields($schemaFields, 'data.', $collection);
        $validator = Validator::make($base, $rules);
        $validator->after(function (\Illuminate\Validation\Validator $v) use ($base, $schemaFields, $collection): void {
            self::validateLayoutFields($v, $base, $schemaFields, $collection);
        });
        $validator->validate();

        return $base;
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
        foreach ($schemaFields as $field) {
            $handle = $field['handle'] ?? null;
            if (!$handle || !is_string($handle)) {
                continue;
            }

            $required = (bool) ($field['required'] ?? false);
            $type = $field['type'] ?? 'text';
            $key = $prefix . $handle;

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
}
