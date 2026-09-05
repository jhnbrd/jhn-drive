<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust Cloudflare Tunnel proxy headers (CF-Connecting-IP, X-Forwarded-Proto, etc.)
        $middleware->trustProxies(at: '*');

        // Exempt /api/* from CSRF for local and LAN client compatibility
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Redirect unauthenticated guests to login
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Secret vault PIN session middleware
        $middleware->alias(['secret.vault.auth' => \App\Http\Middleware\SecretVaultAuth::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
