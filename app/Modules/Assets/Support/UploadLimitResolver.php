<?php

namespace App\Modules\Assets\Support;

final class UploadLimitResolver
{
    public function uploadMaxBytes(): int
    {
        return $this->toBytes((string) ini_get('upload_max_filesize'));
    }

    public function uploadMaxHuman(): string
    {
        return $this->toHuman($this->uploadMaxBytes());
    }

    public function postMaxBytes(): int
    {
        return $this->toBytes((string) ini_get('post_max_size'));
    }

    public function postMaxHuman(): string
    {
        return $this->toHuman($this->postMaxBytes());
    }

    public function effectiveMaxBytes(): int
    {
        $uploadMax = $this->uploadMaxBytes();
        $postMax = $this->postMaxBytes();

        $phpLimit = min(
            $uploadMax > 0 ? $uploadMax : PHP_INT_MAX,
            $postMax > 0 ? $postMax : PHP_INT_MAX
        );

        $capMb = (int) config('cms_assets.max_upload_size_mb', 0);
        $capBytes = $capMb > 0 ? $capMb * 1024 * 1024 : PHP_INT_MAX;

        $effective = min($phpLimit, $capBytes);
        return $effective === PHP_INT_MAX ? 0 : (int) $effective;
    }

    public function effectiveMaxHuman(): string
    {
        return $this->toHuman($this->effectiveMaxBytes());
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $number = (float) $value;
        $unit = strtolower(substr($value, -1));

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    private function toHuman(int $bytes): string
    {
        if ($bytes <= 0) {
            return 'unlimited';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $i = 0;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        return $formatted . ' ' . $units[$i];
    }
}
