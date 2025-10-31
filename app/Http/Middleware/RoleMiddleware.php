<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role)
    {
        $user = Auth::user();

        // Usar Spatie directamente
        if (!$user || !$user->hasRole($role)) {
            abort(403, 'Acceso denegado.');
        }

        return $next($request);
    }
}
