<?php

namespace App\Modules\Assets\Services;

use App\Modules\Assets\Services\Interfaces\ImageTransformServiceInterface;
use App\Support\Exceptions\NotFoundApiException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

final class ImageTransformService implements ImageTransformServiceInterface
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function transform(string $absolutePath, array $params): array
    {
        if (!is_file($absolutePath)) {
            throw new NotFoundApiException('File not found');
        }

        $w = isset($params['w']) ? (int) $params['w'] : null;
        $h = isset($params['h']) ? (int) $params['h'] : null;
        $fit = (string) ($params['fit'] ?? 'contain'); // crop|contain|cover|fill
        $q = isset($params['q']) ? max(1, min(100, (int) $params['q'])) : 80;
        $format = strtolower((string)($params['format'] ?? 'webp')); // webp|jpg|png

        $img = $this->manager->read($absolutePath);

        if ($w || $h) {
            $w = $w ?: $img->width();
            $h = $h ?: $img->height();

            if (in_array($fit, ['crop', 'cover', 'fill'], true)) {
                $img = $img->cover($w, $h);
            } else {
                $img = $img->scaleDown($w, $h);
            }
        }

        $width = $img->width();
        $height = $img->height();

        if ($format === 'jpg' || $format === 'jpeg') {
            $encoded = $img->toJpeg($q);
            return ['bytes' => (string)$encoded, 'mime' => 'image/jpeg', 'ext' => 'jpg', 'width' => $width, 'height' => $height];
        }

        if ($format === 'png') {
            $encoded = $img->toPng();
            return ['bytes' => (string)$encoded, 'mime' => 'image/png', 'ext' => 'png', 'width' => $width, 'height' => $height];
        }

        $encoded = $img->toWebp($q);
        return ['bytes' => (string)$encoded, 'mime' => 'image/webp', 'ext' => 'webp', 'width' => $width, 'height' => $height];
    }

    public function getDimensions(string $absolutePath): array
    {
        if (!is_file($absolutePath)) {
            throw new NotFoundApiException('File not found');
        }

        $img = $this->manager->read($absolutePath);

        return [
            'width' => $img->width(),
            'height' => $img->height(),
        ];
    }
}
