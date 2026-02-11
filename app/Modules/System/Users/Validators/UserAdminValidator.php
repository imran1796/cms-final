<?php

namespace App\Modules\System\Users\Validators;

use App\Models\User;
use App\Support\Exceptions\ValidationApiException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

final class UserAdminValidator
{
    public static function validateCreate(array $input): array
    {
        $v = Validator::make($input, [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles'    => ['sometimes', 'array'],
            'roles.*'  => ['string', Rule::in(self::validRoleNames())],
        ]);

        $data = $v->validate();
        $data['roles'] = $data['roles'] ?? [];

        return $data;
    }

    public static function validateUpdate(array $input, User $user): array
    {
        $v = Validator::make($input, [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'roles'    => ['sometimes', 'array'],
            'roles.*'  => ['string', Rule::in(self::validRoleNames())],
        ]);

        return $v->validate();
    }

    private static function validRoleNames(): array
    {
        return Role::query()->where('guard_name', 'web')->pluck('name')->all();
    }
}
