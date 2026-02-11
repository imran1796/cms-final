<?php

namespace App\Modules\System\Auth\Validators;

use Illuminate\Support\Facades\Validator;

final class ForgotPasswordValidator
{
    public static function validate(array $input): array
    {
        $v = Validator::make($input, [
            'email' => ['required', 'string', 'email'],
        ]);

        return $v->validate();
    }
}
