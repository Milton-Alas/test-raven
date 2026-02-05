<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTestNotCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $candidate = Auth::guard('candidate')->user();

        if ($candidate && $candidate->test_completed) {
            return redirect()->route('candidate.test.completed')
                ->with('info', 'Ya has completado el test anteriormente.');
        }

        return $next($request);
    }
}
