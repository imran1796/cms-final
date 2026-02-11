<?php

namespace App\Modules\Spaces\Middleware;

use App\Models\Space;
use Closure;
use Illuminate\Http\Request;

final class TenantResolverMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $space = null;

        $route = $request->route();
        if ($route) {
            $params = $route->parameters();
            $value = $params['space_id'] ?? $params['space'] ?? $params['spaceId'] ?? $params['id'] ?? null;
            $path = (string) $request->path();

            if ($value !== null && (str_contains($path, 'api/v1/spaces') || isset($params['space_id']))) {
                if (is_numeric($value)) {
                    $space = Space::query()->where('id', (int) $value)->first();
                } else {
                    $space = Space::query()->where('handle', (string) $value)->first();
                }
            }
        }

        if (!$space && config('tenant.subdomain_enabled', false)) {
            $host = $request->getHost();
            $segment = $this->subdomainSegment($host);
            if ($segment !== null && $segment !== '') {
                $space = Space::query()->where('handle', $segment)->first();
            }
        }

        if (!$space) {
            $headerId = $request->header('X-Space-Id');
            $headerHandle = $request->header('X-Space-Handle');

            if ($headerId !== null && $headerId !== '') {
                $space = Space::query()->where('id', (int) $headerId)->first();
            } elseif ($headerHandle !== null && $headerHandle !== '') {
                $space = Space::query()->where('handle', $headerHandle)->first();
            }
        }

        app()->instance('currentSpace', $space);
        app()->instance('currentSpaceId', $space?->id);

        return $next($request);
    }

    private function subdomainSegment(string $host): ?string
    {
        $parts = explode('.', $host);
        if (count($parts) < 2) {
            return null;
        }
        $segment = strtolower($parts[0]);
        $reserved = config('tenant.subdomain_reserved', ['www', 'api', 'app']);
        if (in_array($segment, $reserved, true)) {
            return null;
        }
        return $segment;
    }
}
