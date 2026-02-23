<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class RequestContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $incoming = (string) $request->header('X-Request-Id', '');
        $requestId = $incoming !== '' ? $incoming : (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        $spaceId = $request->header('X-Space-Id');
        $userId = optional($request->user())->id;

        Log::withContext([
            'request_id' => $requestId,
            'space_id'   => $spaceId,
            'user_id'    => $userId,
            'method'     => $request->method(),
            'path'       => $request->path(),
        ]);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
