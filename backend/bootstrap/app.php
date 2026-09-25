<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->ip());
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add ngrok bypass header to all responses
        $middleware->append(\App\Http\Middleware\AddNgrokHeaders::class);

        $middleware->alias([
            'superadmin' => \App\Http\Middleware\IsSuperAdmin::class,
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'customer' => \App\Http\Middleware\IsCustomer::class,
        ]);

        $middleware->group('api', [
            // Parse JSON body first - fixes empty $request->all() under PHP built-in server
            \App\Http\Middleware\ParseJsonBody::class,
            // EnsureFrontendRequestsAreStateful::class, // Commented out for API-only usage
            ThrottleRequests::class . ':api',
            SubstituteBindings::class,
            \App\Http\Middleware\EnsureUserIsActive::class,
            \App\Http\Middleware\CheckTokenExpiration::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON for API authentication/authorization errors
        // instead of attempting to redirect to a named 'login' route.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Let Laravel handle standard HTTP exceptions and Validation errors
                if (
                    $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ||
                    $e instanceof \Illuminate\Validation\ValidationException ||
                    $e instanceof \Illuminate\Auth\AuthenticationException ||
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException
                ) {
                    return null;
                }

                // For all other unexpected errors, return the custom generic message
                return response()->json([
                    'message' => 'Something went wrong. Please try again later.'
                ], 500);
            }
        });
    })->create();
