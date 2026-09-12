<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('test-post', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        /*
         * Registro público de candidatos.
         *
         * Es la única ruta que crea cuentas sin autenticación, así que es el
         * punto natural de abuso automatizado. Se aplican dos límites
         * complementarios:
         *
         *  - 5 registros por hora y por IP: frena la creación masiva de cuentas
         *    desde un mismo origen sin molestar a un usuario real, que se
         *    registra una sola vez.
         *  - 3 intentos por hora sobre el mismo DUI/NIT: evita que se roten IPs
         *    (o que se use una red compartida) para insistir sobre una misma
         *    identidad. Solo se evalúa cuando el formulario trae el dato.
         *
         * Se optó por esto en lugar de un captcha porque no añade dependencias
         * externas, no depende de JavaScript, no agrega fricción al candidato y
         * es verificable desde el servidor.
         */
        RateLimiter::for('register', function (Request $request) {
            return [
                Limit::perHour(5)->by($request->ip()),

                Limit::perHour(3)->by('dui_nit:'.(string) $request->input('dui_nit')),
            ];
        });

        /*
         * Login de candidatos.
         *
         * Sin límite, este endpoint permite probar contraseñas sin fin
         * (fuerza bruta) contra una cuenta concreta. Interesa cubrir dos
         * escenarios distintos, así que se aplican dos límites:
         *
         *  - 5 intentos fallidos por minuto y por IP: frena la prueba masiva de
         *    credenciales desde un mismo origen. El umbral es holgado a
         *    propósito, porque varias personas pueden compartir IP (oficina,
         *    universidad, red móvil) y un usuario legítimo rara vez falla cinco
         *    veces en un minuto.
         *  - 5 intentos fallidos por cada 15 minutos sobre el mismo identificador
         *    (email o DUI/NIT): ataca el caso de la fuerza bruta distribuida, que
         *    rota IPs para insistir sobre una sola cuenta. Se usa una ventana más
         *    larga porque es el vector más peligroso: adivinar la clave de un
         *    candidato concreto.
         *
         * El middleware solo cuenta los intentos FALLIDOS (la respuesta de un
         * login correcto no consume la cuota), así que un candidato que entra
         * bien nunca se ve afectado.
         */
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),

                Limit::perMinutes(15, 5)->by('login:'.Str::lower((string) $request->input('login'))),
            ];
        });
    }
}
