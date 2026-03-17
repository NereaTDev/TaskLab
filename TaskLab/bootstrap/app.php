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
        // Confiar en el proxy de Render (TLS termination en el load balancer)
        $middleware->trustProxies(at: '*');

        // Excluir rutas de integraciones externas de la protección CSRF
        $middleware->validateCsrfTokens(except: [
            'integrations/teams/messages',
            'integrations/discord/messages',
            'integrations/discord/inspect',
        ]);

        // Middleware de alias para verificación de equipo
        $middleware->alias([
            'team.required' => \App\Http\Middleware\EnsureUserHasTeam::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
