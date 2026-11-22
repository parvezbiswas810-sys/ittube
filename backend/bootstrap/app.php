<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->api(prepend: [\App\Http\Middleware\RejectOversizedUploads::class]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
            'admin.perm' => \App\Http\Middleware\EnsureAdminPermission::class,
            'installed' => \App\Http\Middleware\EnsureInstalled::class,
            'not.installed' => \App\Http\Middleware\EnsureNotInstalled::class,
            'app.maintenance' => \App\Http\Middleware\MaintenanceMode::class,
            'guest.browse' => \App\Http\Middleware\AllowGuestBrowsing::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                $errors = $e->errors();
                $first = null;
                foreach ($errors as $messages) {
                    if (is_array($messages) && $messages !== []) {
                        $first = $messages[0];
                        break;
                    }
                }

                return \App\Support\ApiResponse::error($first ?? 'Validation failed', $errors, 422);
            }
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return \App\Support\ApiResponse::error(
                    \App\Support\UploadLimits::tooLargeMessage(),
                    ['video' => [\App\Support\UploadLimits::tooLargeMessage()]],
                    413
                );
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            if (($request->is('api/*') || $request->expectsJson()) && $e->getStatusCode() === 413) {
                return \App\Support\ApiResponse::error(
                    \App\Support\UploadLimits::tooLargeMessage(),
                    ['video' => [\App\Support\UploadLimits::tooLargeMessage()]],
                    413
                );
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Unauthenticated. A valid access token is required.', [], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Resource not found', [], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Too many requests. Please slow down.', [], 429);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Resource not found', [], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*') && $e->getStatusCode() >= 500) {
                return \App\Support\ApiResponse::error('Server error. Please try again later.', [], $e->getStatusCode());
            }
        });
    })->create();
