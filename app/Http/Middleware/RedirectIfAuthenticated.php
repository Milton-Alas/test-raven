<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Redirige a los candidatos a la página de instrucciones
                if ($guard === 'candidate') {
                    return redirect('/instrucciones');
                }

                // Redirección por defecto para otros guards (ej. 'web' para usuarios/admins)
                // Asumiendo que la ruta del dashboard de Filament será la predeterminada.
                return redirect('/dashboard'); 
            }
        }

        return $next($request);
    }
}
