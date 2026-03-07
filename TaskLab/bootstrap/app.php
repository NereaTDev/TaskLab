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
    ->withMiddleware(function (Middleware $middleware): void {
        // Excluir rutas de integraciones externas de la protección CSRF
        $middleware->validateCsrfTokens(except: [
            'integrations/teams/messages',
            'integrations/discord/messages',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
