<?php

use App\Support\RetentionSchedule;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * RNF-09.05 — Aplicación automática de las políticas de retención.
 *
 * La periodicidad es configurable en `config/retention.php` (sección `schedule`,
 * variables `RETENCION_SCHEDULE_*`): frecuencia diaria, semanal, mensual,
 * trimestral o anual, con su hora. El ciclo natural del proceso es el año escolar
 * —los aspirantes a profesorado rinden el test una vez por año—, así que la
 * facultad puede programar la purga una sola vez al año, después del cierre, sin
 * tocar código. La tarea se registra con `--schedule` para que `retention_logs`
 * distinga la ejecución automática de la manual, y admite modo simulación.
 *
 * Un fallo aquí no interrumpe el servicio —ni Filament ni el flujo del candidato
 * dependen de esto—, pero queda registrado en `retention_logs` y en el log de la
 * aplicación.
 *
 * Requiere que el planificador esté activo en el servidor:
 *   * * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1
 *
 * Para revisar el alcance antes de que actúe:
 *   php artisan retention:apply --dry-run
 */
RetentionSchedule::register(
    schedule: app(Schedule::class),
    config: config('retention.schedule', []),
);
