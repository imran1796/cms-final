<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Modules\ModuleServiceProvider;
use App\Http\Middleware\RequestContextMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
    $middleware->append(RequestContextMiddleware::class);
    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);

    })
   
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // If not an API request, let Laravel handle (web pages, etc.)
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }
    
            // Our custom API exceptions
            if ($e instanceof \App\Support\Exceptions\ApiException) {
                $meta = array_merge(
                    ['request_id' => $request->header('X-Request-Id')],
                    $e->meta()
                );
                return \App\Support\ApiResponse::error(
                    message: $e->getMessage(),
                    status: $e->status(),
                    code: $e->codeString(),
                    errors: $e->errors(),
                    meta: $meta
                );
            }
    
            // Laravel validation exception -> our format
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return \App\Support\ApiResponse::error(
                    message: 'Validation failed',
                    status: 422,
                    code: 'VALIDATION_ERROR',
                    errors: $e->errors()
                );
            }
    
            // Auth/authorization (Phase 2 will make this more relevant)
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return \App\Support\ApiResponse::error('Unauthenticated', 401, 'UNAUTHENTICATED');
            }
    
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return \App\Support\ApiResponse::error('Forbidden', 403, 'FORBIDDEN');
            }
    
            // Not found
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return \App\Support\ApiResponse::error('Not Found', 404, 'NOT_FOUND');
            }

            // Model not found (e.g. firstOrFail when collection/entry missing)
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return \App\Support\ApiResponse::error('Not found', 404, 'NOT_FOUND');
            }

            // HttpException (e.g. abort(422)) — return correct status instead of 500
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: 'Error';
                return \App\Support\ApiResponse::error(
                    $message,
                    $status,
                    'HTTP_ERROR',
                    [],
                    ['request_id' => $request->header('X-Request-Id')]
                );
            }
    
            // Fallback: Internal error (log it)
            \Illuminate\Support\Facades\Log::error('Unhandled exception', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            $meta = ['request_id' => $request->header('X-Request-Id')];
            if (config('app.debug')) {
                $meta['debug'] = [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ];
            }
    
            return \App\Support\ApiResponse::error('Internal Server Error', 500, 'INTERNAL_ERROR', [], $meta);
        });
    
    })
    ->withProviders([
        ModuleServiceProvider::class,
    ])->create();
