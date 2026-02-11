<?php

namespace App\Modules\System\Auth\Validators;

use Illuminate\Support\Facades\Validator;

final class ResetPasswordValidator
{
    public static function validate(array $input): array
    {
        $v = Validator::make($input, [
            'email'                 => ['required', 'string', 'email'],
            'token'                 => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        return $v->validate();
    }
}
