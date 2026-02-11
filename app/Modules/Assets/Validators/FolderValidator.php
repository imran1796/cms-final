<?php

namespace App\Modules\Assets\Validators;

use App\Support\Exceptions\ValidationApiException;

final class FolderValidator
{
    public static function validateCreate(array $input): array
    {
        $name = $input['name'] ?? null;
        if ($name === null || $name === '') {
            throw new ValidationApiException('Validation failed', [
                'name' => ['name is required'],
            ]);
        }
        $name = trim((string) $name, '/');
        if ($name === '') {
            throw new ValidationApiException('Validation failed', [
                'name' => ['name cannot be empty'],
            ]);
        }

        $parentId = $input['parent_id'] ?? null;
        if ($parentId !== null && !is_numeric($parentId)) {
            throw new ValidationApiException('Validation failed', [
                'parent_id' => ['parent_id must be numeric or null'],
            ]);
        }

        return [
            'name' => $name,
            'parent_id' => $parentId === null ? null : (int) $parentId,
        ];
    }
}
