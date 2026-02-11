<?php

namespace App\Modules\Spaces\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class SpaceValidator
{
    public static function validateCreate(array $input): array
    {
        $v = Validator::make($input, [
            'handle' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9][a-z0-9\-_]*[a-z0-9]$/',
                Rule::unique('spaces', 'handle'),
            ],
            'name' => ['required', 'string', 'max:160'],
            'settings' => ['sometimes', 'array'],
        ]);

        return $v->validate();
    }
}
