<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Route SNAP publik tanpa prefix /api — untuk callback BRI & BTN
            Route::middleware('api')->prefix('snap')->group(__DIR__.'/../routes/snap.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\QueryStringToken::class);
        $middleware->prepend(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'query_token' => \App\Http\Middleware\QueryStringToken::class,
            'scope_user' => \App\Http\Middleware\SetScopeUser::class,
            'bank_h2h' => \App\Http\Middleware\BankSecurityCheck::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response) {
            // ⚠️ Fix CORS 5 Agu 2026: HANYA echo origin yang di-allow (config/cors.php),
            // bukan semua origin — sebelumnya attacker origin ikut di-echo (celah keamanan
            // + test Genesis QA CORS [different] gagal).
            $origin = request()->headers->get('Origin');
            if ($origin) {
                $allowed = config('cors.allowed_origins', []);
                $patterns = config('cors.allowed_origins_patterns', []);
                $isAllowed = in_array($origin, $allowed, true);
                if (!$isAllowed) {
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $origin)) {
                            $isAllowed = true;
                            break;
                        }
                    }
                }
                if ($isAllowed) {
                    $response->headers->set('Access-Control-Allow-Origin', $origin);
                    $response->headers->set('Access-Control-Allow-Credentials', 'true');
                    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
                }
            }
            return $response;
        });
        \Sentry\Laravel\Integration::handles($exceptions);
    })->create();
// Deploy trigger: Wed Mar 11 08:02:35 WITA 2026
