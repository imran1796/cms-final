<?php

namespace App\Modules\Assets\Validators;

final class AssetTransformValidator
{
    public static function normalizeQuery(array $q): array
    {
        $w = isset($q['w']) && is_numeric($q['w']) ? (int)$q['w'] : null;
        $h = isset($q['h']) && is_numeric($q['h']) ? (int)$q['h'] : null;
        $fit = isset($q['fit']) ? (string)$q['fit'] : null;
        $quality = isset($q['q']) && is_numeric($q['q']) ? (int)$q['q'] : null;
        $format = isset($q['format']) ? (string)$q['format'] : null;

        if ($w !== null) $w = max(1, min($w, 4000));
        if ($h !== null) $h = max(1, min($h, 4000));
        if ($quality !== null) $quality = max(1, min($quality, 100));

        if ($fit !== null && !in_array($fit, ['crop','contain','cover','fill'], true)) {
            $fit = null;
        }

        if ($format !== null && !in_array($format, ['jpg','jpeg','png','webp'], true)) {
            $format = null;
        }

        return [
            'w' => $w,
            'h' => $h,
            'fit' => $fit,
            'q' => $quality,
            'format' => $format,
        ];
    }

    public static function transformKey(?string $presetKey, array $transform): string
    {
        $raw = json_encode(['preset' => $presetKey, 't' => $transform], JSON_UNESCAPED_SLASHES);
        return sha1($raw ?: '');
    }
}
