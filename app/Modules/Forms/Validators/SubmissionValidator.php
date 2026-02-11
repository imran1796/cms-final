<?php

namespace App\Modules\Forms\Validators;

use Illuminate\Support\Facades\Validator;
use App\Support\Exceptions\ValidationApiException;

final class SubmissionValidator
{
    public static function validate(array $formFields, array $payload): array
    {
        $data = $payload['data'] ?? null;
        if (!is_array($data)) {
            throw new ValidationApiException('Validation failed', [
                'data' => ['data must be object'],
            ]);
        }

        $rules = [];
        foreach ($formFields as $f) {
            $name = $f['name'] ?? null;
            if (!$name) continue;

            $fieldRules = [];
            if (!empty($f['required'])) $fieldRules[] = 'required';
            else $fieldRules[] = 'nullable';

            $type = $f['type'] ?? 'text';
            if ($type === 'email') $fieldRules[] = 'email';
            if ($type === 'number') $fieldRules[] = 'numeric';
            if ($type === 'date') $fieldRules[] = 'date';

            if (!empty($f['rules']) && is_array($f['rules'])) {
                foreach ($f['rules'] as $r) $fieldRules[] = (string)$r;
            }

            $rules[$name] = $fieldRules;
        }

        $v = Validator::make($data, $rules);
        if ($v->fails()) {
            throw new ValidationApiException('Validation failed', $v->errors()->toArray());
        }

        return $data;
    }
}
