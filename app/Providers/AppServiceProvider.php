<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
    }
}
