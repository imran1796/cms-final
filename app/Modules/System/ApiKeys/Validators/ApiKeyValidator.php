<?php

namespace App\Modules\System\ApiKeys\Validators;

use Illuminate\Support\Facades\Validator;

final class ApiKeyValidator
{
    public static function validateCreate(array $input): array
    {
        return Validator::make($input, [
            'space_id' => ['nullable', 'integer', 'exists:spaces,id'],
            'name' => ['required', 'string', 'max:160'],
            'scopes' => ['sometimes', 'array'],
            'scopes.collections' => ['sometimes', 'array'],
            'scopes.collections.*' => ['string'],
            'scopes.permissions' => ['sometimes', 'array'],
            'scopes.permissions.*' => ['string'],
        ])->validate();
    }

    public static function validateUpdate(array $input): array
    {
        return Validator::make($input, [
            'space_id' => ['nullable', 'integer', 'exists:spaces,id'],
            'name' => ['sometimes', 'string', 'max:160'],
            'scopes' => ['sometimes', 'array'],
            'scopes.collections' => ['sometimes', 'array'],
            'scopes.collections.*' => ['string'],
            'scopes.permissions' => ['sometimes', 'array'],
            'scopes.permissions.*' => ['string'],
        ])->validate();
    }
}
