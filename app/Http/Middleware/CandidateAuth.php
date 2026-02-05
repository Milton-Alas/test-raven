<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CandidateAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         {
        if (!Auth::guard('candidate')->check()) {
            return redirect()->route('candidate.login')
                ->with('error', 'Debes iniciar sesión para acceder al test.');
        }
        return $next($request);
    }
}
}
