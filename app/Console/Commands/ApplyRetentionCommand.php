<?php

namespace App\Console\Commands;

use App\Models\RetentionLog;
use App\Services\RetentionService;
use Illuminate\Console\Command;

/**
 * RNF-09.05 — Aplicación de las políticas de retención.
 *
 * El modo simulación (--dry-run) recorre exactamente la misma lógica que la
 * ejecución real pero sin escribir cambios. Sirve para dos cosas: revisar el
 * alcance antes de aplicarlo y dejar constancia, en el registro, de que una
 * ejecución no modificó nada.
 */
class ApplyRetentionCommand extends Command
{
    protected $signature = 'retention:apply
                            {--categoria= : Aplica solo esta categoría}
                            {--dry-run : Muestra qué se haría sin modificar nada}
                            {--force : Ejecuta aunque la retención esté desactivada en la configuración}';

    protected $description = 'Aplica las políticas de retención: disocia o suprime los datos que superaron su plazo';

    public function handle(RetentionService $retencion): int
    {
        $simulacion = (bool) $this->option('dry-run');

        if (! $retencion->activa() && ! $this->option('force')) {
            $this->warn('La retención está desactivada (RETENCION_ACTIVA=false). Usa --force para ejecutarla igualmente.');

            return self::SUCCESS;
        }

        $categoria = $this->option('categoria');

        if ($simulacion) {
            $this->info('MODO SIMULACIÓN: no se modificará ningún dato.');
        }

        try {
            $resultados = $retencion->aplicar(
                categoria: $categoria,
                simulacion: $simulacion,
                origen: 'manual',
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Categoría', 'Acción', 'Plazo', 'Corte', 'Afectados', 'Resultado'],
            array_map(fn (RetentionLog $log): array => [
                $log->categoria,
                $log->accion,
                $log->dias_retencion.' días',
                $log->fecha_corte?->format('Y-m-d'),
                $log->registros_afectados,
                $log->resultado,
            ], $resultados)
        );

        foreach ($resultados as $log) {
            if (! empty($log->detalle)) {
                $this->line("  <fg=gray>{$log->categoria}:</> ".json_encode($log->detalle, JSON_UNESCAPED_UNICODE));
            }
        }

        $errores = collect($resultados)->where('resultado', 'error');
        $total = collect($resultados)->sum('registros_afectados');

        $this->newLine();

        if ($simulacion) {
            $this->info("Simulación completada: se verían afectados {$total} registros. No se modificó nada.");
        } else {
            $this->info("Retención aplicada: {$total} registros afectados. Cada operación quedó registrada en retention_logs.");
        }

        if ($errores->isNotEmpty()) {
            $this->error("Hubo {$errores->count()} categoría(s) con error. Revisa retention_logs y el log de la aplicación.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
