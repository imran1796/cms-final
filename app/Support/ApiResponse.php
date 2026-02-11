<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $message = $message ?? 'OK';
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => (object) $meta,
        ], $status);
    }

    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::success($data, $message ?? 'Created', 201);
    }

    public static function error(
        string $message = 'Error',
        int $status = 400,
        ?string $code = null,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'errors'  => (object) $errors,
            'meta'    => (object) $meta,
        ], $status);
    }
}
