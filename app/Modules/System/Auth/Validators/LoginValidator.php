<?php

namespace App\Modules\System\Auth\Validators;

use Illuminate\Support\Facades\Validator;

final class LoginValidator
{
    public static function validate(array $input): array
    {
        $v = Validator::make($input, [
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string', 'min:6'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);

        return $v->validate();
    }
}
