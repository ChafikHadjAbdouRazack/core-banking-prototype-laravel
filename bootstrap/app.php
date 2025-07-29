<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Check if we're on the api subdomain
            $isApiSubdomain = str_starts_with(request()->getHost(), 'api.');
            
            if ($isApiSubdomain) {
                // For api.finaegis.org, load API routes without /api prefix
                Route::middleware('api')
                    ->group(base_path('routes/api.php'));
                    
                Route::middleware('api')
                    ->group(base_path('routes/api-bian.php'));

                Route::middleware('api')
                    ->prefix('v2')
                    ->group(base_path('routes/api-v2.php'));
            } else {
                // For main domain, keep the /api prefix
                Route::middleware('api')
                    ->prefix('api')
                    ->group(base_path('routes/api-bian.php'));

                Route::middleware('api')
                    ->prefix('api/v2')
                    ->group(base_path('routes/api-v2.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register rate limiting middleware
        $middleware->alias([
            'api.rate_limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
            'transaction.rate_limit' => \App\Http\Middleware\TransactionRateLimitMiddleware::class,
            'ensure.json' => \App\Http\Middleware\EnsureJsonRequest::class,
            'check.token.expiration' => \App\Http\Middleware\CheckTokenExpiration::class,
            'sub_product' => \App\Http\Middleware\EnsureSubProductEnabled::class,
            'auth.apikey' => \App\Http\Middleware\AuthenticateApiKey::class,
            'auth.api_or_sanctum' => \App\Http\Middleware\AuthenticateApiOrSanctum::class,
            'idempotency' => \App\Http\Middleware\IdempotencyMiddleware::class,
            'webhook.signature' => \App\Http\Middleware\ValidateWebhookSignature::class,
        ]);

        // Prepend CORS middleware to handle it before other middleware
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        // Apply middleware to API routes (no global throttling - use custom rate limiting)
        $middleware->group('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Apply security headers to web routes
        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Treat 'demo' environment as production for error handling
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });

        // Don't report certain exceptions in demo environment
        $exceptions->dontReport([
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Illuminate\Validation\ValidationException::class,
        ]);
    })->create();
