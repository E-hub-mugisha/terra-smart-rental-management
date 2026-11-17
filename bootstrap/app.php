<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);

        // Payment providers call this webhook server-to-server, without a CSRF token.
        // It only triggers a re-check with the provider, so it cannot fake a payment.
        $middleware->validateCsrfTokens(except: ['payments/callback/*']);

        // Per-user language (brief §23).
        $middleware->web(append: [\App\Http\Middleware\SetLocale::class]);
        $middleware->api(append: [\App\Http\Middleware\SetLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
