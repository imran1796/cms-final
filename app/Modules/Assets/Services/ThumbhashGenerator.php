<?php

namespace App\Modules\Assets\Services;

use Thumbhash\Thumbhash;

final class ThumbhashGenerator
{
    private const MAX_SIZE = 100;

    public static function generate(string $absolutePath): ?string
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $img = self::loadImage($absolutePath);
        if ($img === null) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 1 || $h < 1) {
            imagedestroy($img);
            return null;
        }

        if ($w > self::MAX_SIZE || $h > self::MAX_SIZE) {
            $scale = min(self::MAX_SIZE / $w, self::MAX_SIZE / $h);
            $nw = (int) round($w * $scale);
            $nh = (int) round($h * $scale);
            $nw = max(1, min($nw, self::MAX_SIZE));
            $nh = max(1, min($nh, self::MAX_SIZE));
            $resized = imagecreatetruecolor($nw, $nh);
            if ($resized === false) {
                imagedestroy($img);
                return null;
            }
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $resized;
            $w = $nw;
            $h = $nh;
        }

        $rgba = self::extractRgba($img, $w, $h);
        imagedestroy($img);
        if ($rgba === null) {
            return null;
        }

        try {
            $hash = Thumbhash::RGBAToHash($w, $h, $rgba);
            return Thumbhash::convertHashToString($hash);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function loadImage(string $path): ?\GdImage
    {
        $blob = @file_get_contents($path);
        if ($blob === false) {
            return null;
        }
        $img = @imagecreatefromstring($blob);
        return $img instanceof \GdImage ? $img : null;
    }

    private static function extractRgba(\GdImage $img, int $w, int $h): ?array
    {
        $rgba = [];
        $truecolor = imageistruecolor($img);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($img, $x, $y);
                if ($c === false) {
                    return null;
                }
                if ($truecolor) {
                    $r = ($c >> 16) & 0xFF;
                    $g = ($c >> 8) & 0xFF;
                    $b = $c & 0xFF;
                    $gdAlpha = ($c >> 24) & 0x7F;
                    $a = (int) round((127 - $gdAlpha) * 255 / 127);
                } else {
                    $colors = imagecolorsforindex($img, $c);
                    if (!is_array($colors)) {
                        return null;
                    }
                    $r = $colors['red'] ?? 0;
                    $g = $colors['green'] ?? 0;
                    $b = $colors['blue'] ?? 0;
                    $gdAlpha = $colors['alpha'] ?? 0;
                    $a = (int) round((127 - $gdAlpha) * 255 / 127);
                }
                $rgba[] = $r;
                $rgba[] = $g;
                $rgba[] = $b;
                $rgba[] = $a;
            }
        }
        return $rgba;
    }
}
