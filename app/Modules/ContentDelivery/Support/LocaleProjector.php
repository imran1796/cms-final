<?php

namespace App\Modules\ContentDelivery\Support;

final class LocaleProjector
{
    public static function project(array $data, string $locale): array
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                if (array_key_exists($locale, $v) && (is_string($v[$locale]) || is_numeric($v[$locale]))) {
                    $data[$k] = $v[$locale];
                    continue;
                }

                $data[$k] = self::project($v, $locale);
            }
        }
        return $data;
    }
}
