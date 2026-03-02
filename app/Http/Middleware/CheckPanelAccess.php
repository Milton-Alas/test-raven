<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPanelAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $panel = Filament::getCurrentPanel();

        // Verificamos si hay usuario y si tiene permiso
        if (
            !$user ||
            !$panel ||
            !($user instanceof FilamentUser) ||
            !$user->canAccessPanel($panel)
        ) {
            abort(403, 'Acceso denegado: No tienes permisos para entrar al panel.');
        }

        return $next($request);
    }
}
