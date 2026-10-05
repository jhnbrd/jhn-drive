<?php

use App\Http\Middleware\SecretVaultAuth;
use App\Support\UploadLimits;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust Cloudflare Tunnel proxy headers (CF-Connecting-IP, X-Forwarded-Proto, etc.)
        $middleware->trustProxies(at: '*');

        // Redirect unauthenticated guests to login
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Secret vault PIN session middleware
        $middleware->alias(['secret.vault.auth' => SecretVaultAuth::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if (! $request->is('api/upload') && ! $request->is('secret/upload')) {
                return null;
            }

            $limits = UploadLimits::clientConfig();

            return response()->json([
                'success' => false,
                'message' => "Upload rejected by the server. Maximum request size is {$limits['max_request_human']}.",
                'upload_limits' => $limits,
            ], 413);
        });
    })->create();
