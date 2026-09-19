<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * RNF-09.05 — Aplicación automática de las políticas de retención.
 *
 * Se ejecuta a diario de madrugada, en horario de baja actividad. Filament y el
 * flujo del candidato no dependen de esto, así que un fallo aquí no interrumpe
 * el servicio, pero sí queda registrado como error en `retention_logs` y en el
 * log de la aplicación.
 *
 * Requiere que el planificador esté activo en el servidor:
 *   * * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1
 *
 * Para revisar el alcance antes de que actúe:
 *   php artisan retention:apply --dry-run
 */
Schedule::command('retention:apply')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Aplica las políticas de retención de datos (RNF-09.05)');
