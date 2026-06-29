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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'auth.jwt' => \App\Http\Middleware\EnsureJwtAuthenticated::class,
            'auth.superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'auth.admin_only' => \App\Http\Middleware\RestrictSellerAccess::class,
            'auth.module' => \App\Http\Middleware\EnsureModuleAccess::class,
            'initialize.branch' => \App\Http\Middleware\InitializeActiveBranch::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
