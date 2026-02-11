<?php

namespace App\Modules\ContentTree\Validators;

use Illuminate\Support\Facades\Validator;

final class TreeReorderValidator
{
    public static function validate(array $data): array
    {
        $v = Validator::make($data, [
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'order'     => ['required', 'array', 'min:1'],
            'order.*'   => ['integer', 'min:1'],
        ]);

        if ($v->fails()) {
            throw new \App\Support\Exceptions\ValidationException($v->errors()->toArray());
        }

        return $v->validated();
    }
}
