<?php

namespace App\Modules\System\Auth\Validators;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class UpdateProfileValidator
{
    public static function validate(array $input, User $user): array
    {
        $v = Validator::make($input, [
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        return $v->validate();
    }
}
