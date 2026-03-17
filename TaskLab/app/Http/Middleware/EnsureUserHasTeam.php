<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTeam
{
    // Rutas que un usuario sin equipo puede visitar
    private const ALLOWED_ROUTES = [
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'pending-team',
        'password.update',
        'verification.notice',
        'verification.send',
        'verification.verify',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // SuperAdmin siempre tiene acceso total
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Usuarios creados externamente (Discord/Teams requesters) no necesitan equipo
        if ($user->user_type === 'requester') {
            return $next($request);
        }

        // Rutas permitidas sin equipo
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        // Si no tiene equipo → pantalla de espera
        if (! $user->hasTeam()) {
            return redirect()->route('pending-team');
        }

        return $next($request);
    }
}
