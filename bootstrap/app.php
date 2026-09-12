<?php

use App\Http\Middleware\CandidateAuth;
use App\Http\Middleware\EnsureTestNotCompleted;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'candidate.auth' => CandidateAuth::class,
            'test.not.completed' => EnsureTestNotCompleted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Límite de intentos alcanzado (throttle del registro público y del
         * login de candidatos).
         *
         * En lugar de la página de error 429 sin contexto, se devuelve al
         * formulario con un mensaje claro y el tiempo de espera. Se conserva el
         * código 429 en la respuesta para que el comportamiento siga siendo
         * correcto de cara a clientes y monitoreo.
         */
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 0);

            // La espera se expresa en segundos, minutos u horas según la ventana
            // del limitador: el login bloquea por minutos y el registro por horas.
            $espera = match (true) {
                $retryAfter <= 0 => null,
                $retryAfter < 60 => "{$retryAfter} segundo(s)",
                $retryAfter < 3600 => max(1, (int) ceil($retryAfter / 60)).' minuto(s)',
                default => max(1, (int) ceil($retryAfter / 3600)).' hora(s)',
            };

            $mensaje = $espera
                ? "Se alcanzó el límite de intentos. Vuelve a intentarlo en {$espera}."
                : 'Se alcanzó el límite de intentos. Vuelve a intentarlo más tarde.';

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $mensaje)
                ->withHeaders(['Retry-After' => (string) $retryAfter])
                ->setStatusCode(429);
        });
    })->create();
