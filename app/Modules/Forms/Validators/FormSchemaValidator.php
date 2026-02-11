<?php

namespace App\Modules\Forms\Validators;

use Illuminate\Support\Str;
use App\Support\Exceptions\ValidationApiException;

final class FormSchemaValidator
{
    public static function validate(array $input): array
    {
        $errors = [];

        $handle = (string)($input['handle'] ?? '');
        $title  = (string)($input['title'] ?? '');
        $fields = $input['fields'] ?? null;
        $settings = $input['settings'] ?? null;

        if ($handle === '' || !preg_match('/^[a-z0-9_]+$/', $handle)) {
            $errors['handle'][] = 'handle is required and must be snake_case (a-z0-9_)';
        }

        if ($title === '') {
            $errors['title'][] = 'title is required';
        }

        if (!is_array($fields) || count($fields) === 0) {
            $errors['fields'][] = 'fields must be a non-empty array';
        } else {
            $names = [];
            foreach ($fields as $i => $f) {
                if (!is_array($f)) {
                    $errors["fields.$i"][] = 'field must be an object';
                    continue;
                }

                $name = (string)($f['name'] ?? '');
                $type = (string)($f['type'] ?? '');
                if ($name === '' || !preg_match('/^[a-z0-9_]+$/', $name)) {
                    $errors["fields.$i.name"][] = 'name required and must be snake_case';
                } else {
                    if (in_array($name, $names, true)) {
                        $errors["fields.$i.name"][] = 'duplicate field name';
                    }
                    $names[] = $name;
                }

                $allowedTypes = ['text','textarea','email','number','select','checkbox','date'];
                if ($type === '' || !in_array($type, $allowedTypes, true)) {
                    $errors["fields.$i.type"][] = 'invalid type';
                }

                if (isset($f['rules']) && !is_array($f['rules'])) {
                    $errors["fields.$i.rules"][] = 'rules must be array';
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationApiException('Validation failed', $errors);
        }

        return [
            'handle' => $handle,
            'title' => $title,
            'fields' => $fields,
            'settings' => is_array($settings) ? $settings : null,
        ];
    }
}
