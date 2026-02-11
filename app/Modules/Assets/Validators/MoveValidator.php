<?php

namespace App\Modules\Assets\Validators;

use App\Support\Exceptions\ValidationApiException;

final class MoveValidator
{
    public static function validate(array $input): array
    {
        $ids = $input['ids'] ?? null;
        $folderId = $input['folder_id'] ?? null;

        if (!is_array($ids) || count($ids) === 0) {
            throw new ValidationApiException('Validation failed', [
                'ids' => ['ids must be non-empty array'],
            ]);
        }

        foreach ($ids as $i => $id) {
            if (!is_numeric($id)) {
                throw new ValidationApiException('Validation failed', [
                    "ids.$i" => ['id must be numeric'],
                ]);
            }
        }

        if ($folderId !== null && !is_numeric($folderId)) {
            throw new ValidationApiException('Validation failed', [
                'folder_id' => ['folder_id must be numeric or null'],
            ]);
        }

        return [
            'ids' => array_map(fn($x) => (int)$x, $ids),
            'folder_id' => $folderId === null ? null : (int)$folderId,
        ];
    }
}
