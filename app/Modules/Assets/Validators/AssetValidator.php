<?php

namespace App\Modules\Assets\Validators;

use Illuminate\Http\Request;
use App\Support\Exceptions\ValidationApiException;

final class AssetValidator
{
    public static function validateUpload(Request $request): array
    {
        if (!$request->hasFile('file')) {
            throw new ValidationApiException('Validation failed', [
                'file' => ['file is required (multipart/form-data)'],
            ]);
        }

        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            throw new ValidationApiException('Validation failed', [
                'file' => ['invalid upload'],
            ]);
        }

        $folderId = $request->input('folder_id');
        if ($folderId !== null && !is_numeric($folderId)) {
            throw new ValidationApiException('Validation failed', [
                'folder_id' => ['folder_id must be numeric'],
            ]);
        }

        return [
            'file' => $file,
            'folder_id' => $folderId ? (int)$folderId : null,
        ];
    }

    public static function validateUpdate(array $input): array
    {
        $out = [];

        if (array_key_exists('filename', $input)) {
            $name = (string)$input['filename'];
            if ($name === '') {
                throw new ValidationApiException('Validation failed', ['filename' => ['filename cannot be empty']]);
            }
            $out['filename'] = $name;
        }

        if (array_key_exists('folder_id', $input)) {
            $fid = $input['folder_id'];
            if ($fid !== null && !is_numeric($fid)) {
                throw new ValidationApiException('Validation failed', ['folder_id' => ['folder_id must be numeric or null']]);
            }
            $out['folder_id'] = $fid === null ? null : (int)$fid;
        }

        return $out;
    }
}
