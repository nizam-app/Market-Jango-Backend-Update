<?php

use App\Http\Middleware\CheckUserStatusMiddleware;
use App\Http\Middleware\SetLanguageMiddleware;
use App\Http\Middleware\TokenVerifyMiddleware;
use App\Http\Middleware\UserTypeVerificationMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tokenVerify' => TokenVerifyMiddleware::class,
            'userTypeVerify' => UserTypeVerificationMiddleware::class,
            'statusVerify' => CheckUserStatusMiddleware::class,
            'language' => SetLanguageMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'verify.permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
