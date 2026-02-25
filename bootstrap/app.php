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
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->cors(
            paths: ['api/*', 'sanctum/csrf-cookie'],
            allowedOrigins: [
                'http://localhost:3000',
                'http://localhost:3001',
                'http://localhost:3002',
                'http://localhost:3003',
                'https://admin.sipanda.online',
                'https://petugas.sipanda.online',
                'https://sipanda.online',
                'https://adminwiyasa.site',
                env('FRONTEND_URL', 'http://localhost:3000')
            ],
            allowedHeaders: ['*'],
            allowedMethods: ['*'],
            exposedHeaders: [],
            maxAge: 0,
            supportsCredentials: true
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        \Sentry\Laravel\Integration::handles($exceptions);
    })->create();
