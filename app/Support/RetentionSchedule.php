<?php

namespace App\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Programación de la aplicación automática de la retención (RNF-09.05).
 *
 * La periodicidad es configurable (`config/retention.php`, sección `schedule`,
 * variables `RETENCION_SCHEDULE_*`), así que la institución decide cuándo se
 * aplica la política sin tocar código ni volver a desplegar. El ciclo real del
 * proceso es el año escolar: los aspirantes a profesorado rinden el test una vez
 * por año, de modo que la purga puede programarse una sola vez al año —después
 * del cierre— en lugar de a diario.
 *
 * La tarea programada llama siempre a `retention:apply` con `--schedule`, para que
 * `retention_logs.origen` distinga la ejecución automática de la manual. Si la
 * configuración pide simulación, se añade `--dry-run`: recorre la misma lógica sin
 * modificar nada y deja constancia con `simulacion = true`, lo que permite
 * desplegar en staging y ver el alcance real de la política antes de activarla.
 */
final class RetentionSchedule
{
    /**
     * Registra la tarea en el planificador.
     *
     * @param  array<string, mixed>  $config  Sección `retention.schedule`.
     * @return Event|null El evento programado, o null si está desactivado.
     */
    public static function register(Schedule $schedule, array $config): ?Event
    {
        if (! ($config['activa'] ?? true)) {
            return null;
        }

        $comando = self::command($config);
        $hora = (string) ($config['hora'] ?? '03:00');

        $evento = match ($config['frecuencia'] ?? 'daily') {
            'weekly' => $schedule->command($comando)->weeklyOn((int) ($config['dia_semana'] ?? 1), $hora),
            'monthly' => $schedule->command($comando)->monthlyOn((int) ($config['dia_mes'] ?? 1), $hora),
            'quarterly' => $schedule->command($comando)->quarterlyOn((int) ($config['dia_mes'] ?? 1), $hora),
            'yearly' => $schedule->command($comando)->yearlyOn((int) ($config['mes'] ?? 1), (int) ($config['dia_mes'] ?? 1), $hora),
            default => $schedule->command($comando)->dailyAt($hora),
        };

        return $evento
            ->withoutOverlapping()
            ->onOneServer()
            ->description('Aplica las políticas de retención de datos (RNF-09.05)');
    }

    /**
     * Comando programado, con las opciones que fija la configuración.
     *
     * @param  array<string, mixed>  $config
     */
    private static function command(array $config): string
    {
        return 'retention:apply --schedule'.(($config['simulacion'] ?? false) ? ' --dry-run' : '');
    }
}
