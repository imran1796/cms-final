<?php

namespace App\Modules\Content\Validators;

use App\Support\Exceptions\ValidationApiException;

final class ContentTypeValidator
{
    public static function validateCreate(array $payload): array
    {
        $handle = (string) ($payload['handle'] ?? '');
        $type   = (string) ($payload['type'] ?? '');
        $fields = $payload['fields'] ?? [];
        $settings = $payload['settings'] ?? [];

        if ($handle === '') {
            throw new ValidationApiException(['handle' => ['handle is required']]);
        }

        if (!in_array($type, ['collection', 'singleton', 'tree'], true)) {
            throw new ValidationApiException(['type' => ['type must be collection|singleton|tree']]);
        }

        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            if (!is_array($decoded)) {
                throw new ValidationApiException(['fields' => ['fields must be a valid JSON array']]);
            }
            $fields = $decoded;
        }

        if (!is_array($fields)) {
            throw new ValidationApiException(['fields' => ['fields must be an array']]);
        }

        if (is_string($settings)) {
            $decoded = json_decode($settings, true);
            if (!is_array($decoded)) {
                throw new ValidationApiException(['settings' => ['settings must be a valid JSON object']]);
            }
            $settings = $decoded;
        }
        if (!is_array($settings)) {
            throw new ValidationApiException(['settings' => ['settings must be an object/array']]);
        }

        $ids = [];
        foreach ($fields as $i => $field) {
            if (!is_array($field)) {
                throw new ValidationApiException(['fields' => ["fields[$i] must be an object"]]);
            }

            $id   = (string) ($field['id'] ?? $field['name'] ?? '');
            $name = (string) ($field['name'] ?? $field['id'] ?? '');
            $ft   = (string) ($field['type'] ?? '');

            if ($id === '' || $name === '') {
                throw new ValidationApiException(['fields' => ["fields[$i] must have id or name"]]);
            }

            if ($ft === '') {
                throw new ValidationApiException(['fields' => ["fields[$i].type is required"]]);
            }

            if (isset($ids[$id])) {
                throw new ValidationApiException(['fields' => ["duplicate field id: $id"]]);
            }
            $ids[$id] = true;
        }

        $payload['fields'] = $fields;
        $payload['settings'] = $settings;

        return $payload;
    }

    public static function validateUpdate(array $payload, ?int $spaceId, int $collectionId): array
    {
        $handle = (string) ($payload['handle'] ?? '');
        $type = (string) ($payload['type'] ?? '');
        $fields = $payload['fields'] ?? null;
        $settings = $payload['settings'] ?? null;

        if ($type !== '' && !in_array($type, ['collection', 'singleton', 'tree'], true)) {
            throw new ValidationApiException(['type' => ['type must be collection|singleton|tree']]);
        }

        if ($fields !== null) {
            if (is_string($fields)) {
                $decoded = json_decode($fields, true);
                if (!is_array($decoded)) {
                    throw new ValidationApiException(['fields' => ['fields must be a valid JSON array']]);
                }
                $fields = $decoded;
            }

            if (!is_array($fields)) {
                throw new ValidationApiException(['fields' => ['fields must be an array']]);
            }

            $ids = [];
            foreach ($fields as $i => $field) {
                if (!is_array($field)) {
                    throw new ValidationApiException(['fields' => ["fields[$i] must be an object"]]);
                }

                $id = (string) ($field['id'] ?? $field['name'] ?? '');
                $name = (string) ($field['name'] ?? $field['id'] ?? '');
                $ft = (string) ($field['type'] ?? '');

                if ($id === '' || $name === '') {
                    throw new ValidationApiException(['fields' => ["fields[$i] must have id or name"]]);
                }

                if ($ft === '') {
                    throw new ValidationApiException(['fields' => ["fields[$i].type is required"]]);
                }

                if (isset($ids[$id])) {
                    throw new ValidationApiException(['fields' => ["duplicate field id: $id"]]);
                }
                $ids[$id] = true;
            }

            $payload['fields'] = $fields;
        }

        if ($settings !== null) {
            if (is_string($settings)) {
                $decoded = json_decode($settings, true);
                if (!is_array($decoded)) {
                    throw new ValidationApiException(['settings' => ['settings must be a valid JSON object']]);
                }
                $settings = $decoded;
            }
            if (!is_array($settings)) {
                throw new ValidationApiException(['settings' => ['settings must be an object/array']]);
            }
            $payload['settings'] = $settings;
        }

        return $payload;
    }

    public static function validateField(array $fieldInput): array
    {
        $handle = (string) ($fieldInput['handle'] ?? $fieldInput['id'] ?? $fieldInput['name'] ?? '');
        $label = (string) ($fieldInput['label'] ?? '');
        $type = (string) ($fieldInput['type'] ?? '');
        $required = (bool) ($fieldInput['required'] ?? false);

        if ($handle === '') {
            throw new ValidationApiException(['handle' => ['Field handle (or id/name) is required']]);
        }

        if ($label === '') {
            $label = ucwords(str_replace(['_', '-'], ' ', $handle));
        }

        if ($type === '') {
            throw new ValidationApiException(['type' => ['Field type is required']]);
        }

        $validTypes = ['text', 'textarea', 'richtext', 'number', 'boolean', 'date', 'datetime', 'time', 'json', 'select', 'radio', 'tags', 'enum', 'repeater', 'layout', 'relation', 'asset', 'assets', 'slug', 'password', 'color'];
        if (!in_array($type, $validTypes, true)) {
            throw new ValidationApiException(['type' => ['Field type must be one of: ' . implode(', ', $validTypes)]]);
        }

        $localizedAllowedTypes = ['text', 'textarea', 'richtext', 'slug', 'number', 'boolean', 'date', 'datetime', 'time', 'json', 'select', 'radio', 'enum', 'color'];
        $localized = (bool) ($fieldInput['localized'] ?? false);
        if ($localized && !in_array($type, $localizedAllowedTypes, true)) {
            throw new ValidationApiException('Validation failed', ['localized' => ['localized is only allowed for: ' . implode(', ', $localizedAllowedTypes)]]);
        }

        $out = [
            'handle' => $handle,
            'label' => $label,
            'type' => $type,
            'required' => $required,
            'localized' => $localized,
        ];

        if ($type === 'slug') {
            $out['source_field'] = isset($fieldInput['source_field']) ? (string) $fieldInput['source_field'] : null;
        }

        if ($type === 'color') {
            $opts = $fieldInput['options'] ?? $fieldInput['palette'] ?? [];
            $values = isset($opts['values']) && is_array($opts['values']) ? $opts['values'] : (is_array($opts) && array_is_list($opts) ? $opts : []);
            $out['options'] = ['values' => array_values(array_map('strval', $values))];
        }

        if (in_array($type, ['asset', 'assets'], true)) {
            $kinds = $fieldInput['allowed_kinds'] ?? [];
            $out['allowed_kinds'] = is_array($kinds) ? array_values(array_map('strval', $kinds)) : [];
        }

        if ($type === 'relation') {
            $rel = $fieldInput['relation'] ?? [];
            $refHandle = (string) ($rel['collection'] ?? '');
            if ($refHandle === '') {
                throw new ValidationApiException(['relation' => ['relation.collection is required for relation fields']]);
            }
            $max = isset($rel['max']) ? $rel['max'] : null;
            if ($max !== null && $max !== 1) {
                $max = null; // normalize to null for "multiple"
            }
            $out['relation'] = ['collection' => $refHandle, 'max' => $max];
        }

        if (in_array($type, ['select', 'radio', 'tags', 'enum'], true)) {
            $opts = $fieldInput['options'] ?? [];
            if (isset($opts['values']) && is_array($opts['values'])) {
                $out['options'] = ['values' => array_values(array_map('strval', $opts['values']))];
            } elseif (is_array($opts) && array_is_list($opts)) {
                $out['options'] = ['values' => array_values(array_map('strval', $opts))];
            } else {
                $out['options'] = ['values' => []];
            }
        }

        if ($type === 'repeater') {
            $nested = $fieldInput['fields'] ?? [];
            if (!is_array($nested)) {
                $nested = [];
            }
            $out['fields'] = [];
            foreach ($nested as $i => $nestedInput) {
                if (!is_array($nestedInput)) {
                    throw new ValidationApiException(['fields' => ["repeater fields[$i] must be an object"]]);
                }
                $out['fields'][] = self::validateField($nestedInput);
            }
        }

        if ($type === 'layout') {
            $blocksInput = $fieldInput['blocks'] ?? [];
            if (!is_array($blocksInput)) {
                $blocksInput = [];
            }
            $out['blocks'] = [];
            $blockTypes = [];
            foreach ($blocksInput as $i => $blockInput) {
                if (!is_array($blockInput)) {
                    throw new ValidationApiException(['blocks' => ["layout blocks[$i] must be an object"]]);
                }
                $blockType = (string) ($blockInput['type'] ?? '');
                if ($blockType === '') {
                    throw new ValidationApiException(['blocks' => ["layout blocks[$i].type is required"]]);
                }
                if (in_array($blockType, $blockTypes, true)) {
                    throw new ValidationApiException(['blocks' => ["layout block type must be unique: $blockType"]]);
                }
                $blockTypes[] = $blockType;
                $blockLabel = (string) ($blockInput['label'] ?? $blockType);
                $nested = $blockInput['fields'] ?? [];
                if (!is_array($nested)) {
                    $nested = [];
                }
                $blockFields = [];
                foreach ($nested as $j => $nestedInput) {
                    if (!is_array($nestedInput)) {
                        throw new ValidationApiException(['blocks' => ["layout blocks[$i].fields[$j] must be an object"]]);
                    }
                    $blockFields[] = self::validateField($nestedInput);
                }
                $out['blocks'][] = [
                    'type' => $blockType,
                    'label' => $blockLabel,
                    'fields' => $blockFields,
                ];
            }
            if (count($out['blocks']) === 0) {
                throw new ValidationApiException(['blocks' => ['layout must have at least one block type']]);
            }
        }

        return $out;
    }
}
