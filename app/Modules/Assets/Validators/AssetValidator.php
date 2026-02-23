<?php

namespace App\Modules\Assets\Validators;

use App\Modules\Assets\Support\UploadLimitResolver;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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

        self::assertFilePolicy($file);

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

    public static function validateMergedFile(string $path, string $originalFilename): void
    {
        if (!self::strictValidationEnabled()) {
            return;
        }

        if (!is_file($path)) {
            throw new ValidationApiException('Validation failed', [
                'file' => ['merged upload file is missing'],
            ]);
        }

        $maxBytes = self::maxUploadBytes();
        $size = (int) (filesize($path) ?: 0);
        if ($maxBytes > 0 && $size > $maxBytes) {
            throw new ValidationApiException('Validation failed', [
                'file' => ['file exceeds maximum upload size'],
            ]);
        }

        $mime = strtolower((string) (File::mimeType($path) ?: 'application/octet-stream'));
        $ext = strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION));

        $allowedMimes = self::allowedMimeTypes();
        if ($allowedMimes !== [] && !in_array($mime, $allowedMimes, true)) {
            throw new ValidationApiException('Validation failed', [
                'file' => ["mime type {$mime} is not allowed"],
            ]);
        }

        $allowedExtensions = self::allowedExtensions();
        if ($allowedExtensions !== [] && !in_array($ext, $allowedExtensions, true)) {
            throw new ValidationApiException('Validation failed', [
                'file' => ["file extension .{$ext} is not allowed"],
            ]);
        }
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

    private static function assertFilePolicy(UploadedFile $file): void
    {
        if (!self::strictValidationEnabled()) {
            return;
        }

        $maxBytes = self::maxUploadBytes();
        $size = (int) ($file->getSize() ?: 0);
        if ($maxBytes > 0 && $size > $maxBytes) {
            throw new ValidationApiException('Validation failed', [
                'file' => ['file exceeds maximum upload size'],
            ]);
        }

        $mime = strtolower((string) ($file->getMimeType() ?: 'application/octet-stream'));
        $ext = strtolower((string) ($file->extension() ?: $file->getClientOriginalExtension()));

        $allowedMimes = self::allowedMimeTypes();
        if ($allowedMimes !== [] && !in_array($mime, $allowedMimes, true)) {
            throw new ValidationApiException('Validation failed', [
                'file' => ["mime type {$mime} is not allowed"],
            ]);
        }

        $allowedExtensions = self::allowedExtensions();
        if ($allowedExtensions !== [] && !in_array($ext, $allowedExtensions, true)) {
            throw new ValidationApiException('Validation failed', [
                'file' => ["file extension .{$ext} is not allowed"],
            ]);
        }
    }

    private static function strictValidationEnabled(): bool
    {
        return (bool) config('cms_assets.strict_upload_validation', false);
    }

    private static function maxUploadBytes(): int
    {
        return (new UploadLimitResolver())->effectiveMaxBytes();
    }

    private static function allowedMimeTypes(): array
    {
        $mimes = config('cms_assets.allowed_mime_types', []);
        if (!is_array($mimes)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn($m) => strtolower(trim((string) $m)),
            $mimes
        ))));
    }

    private static function allowedExtensions(): array
    {
        $extensions = config('cms_assets.allowed_extensions', []);
        if (!is_array($extensions)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn($e) => strtolower(ltrim(trim((string) $e), '.')),
            $extensions
        ))));
    }
}
