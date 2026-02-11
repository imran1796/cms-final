<?php

namespace App\Modules\SavedViews\Validators;

use Illuminate\Support\Facades\Validator;
use App\Support\Exceptions\ValidationApiException;

final class SavedViewValidator
{
    public static function validateCreate(array $input): array
    {
        $v = Validator::make($input, [
            'resource' => ['required', 'string', 'max:120'],
            'name'     => ['required', 'string', 'max:120'],
            'config'   => ['required', 'array'],

            'config.columns' => ['sometimes', 'array'],
            'config.filters' => ['sometimes', 'array'],
            'config.sort'    => ['sometimes', 'string', 'max:120'],
            'config.limit'   => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        if ($v->fails()) {
            throw new ValidationApiException('Validation failed', $v->errors()->toArray());
        }

        return $v->validated();
    }

    public static function validateUpdate(array $input): array
    {
        $v = Validator::make($input, [
            'name'   => ['sometimes', 'string', 'max:120'],
            'config' => ['sometimes', 'array'],

            'config.columns' => ['sometimes', 'array'],
            'config.filters' => ['sometimes', 'array'],
            'config.sort'    => ['sometimes', 'string', 'max:120'],
            'config.limit'   => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        if ($v->fails()) {
            throw new ValidationApiException('Validation failed', $v->errors()->toArray());
        }

        return $v->validated();
    }
}
