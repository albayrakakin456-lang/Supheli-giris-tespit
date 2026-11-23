<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // API rotaları: otomatik olarak /api ön eki alır (install:api yerine elle açtık, Sanctum yok)
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Rotalarda ->middleware('rol:yonetici') diye kullanabilmek için kısa ad
        $middleware->alias([
            'rol' => \App\Http\Middleware\KeycloakRol::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
