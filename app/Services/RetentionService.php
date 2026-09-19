<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\RetentionLog;
use App\Models\TestResult;
use App\Models\TestSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * RNF-09.04 / RNF-09.05 — Aplicación de las políticas de retención.
 *
 * Recorre las categorías configuradas en config/retention.php y, para cada una,
 * aplica la acción que corresponda a los registros que superaron su plazo:
 *
 *   - **disociar**: elimina los identificadores directos y conserva el dato
 *     desagregado. Es lo que permite mantener la estadística del instrumento sin
 *     conservar datos personales.
 *   - **suprimir**: elimina el registro por completo (o el campo, en los datos
 *     técnicos, que no aportan valor desagregado).
 *
 * Todo lo que hace queda registrado en `retention_logs`, que es la evidencia del
 * requisito. El modo simulación recorre exactamente la misma lógica pero sin
 * escribir, para poder mostrar el alcance antes de aplicarlo.
 */
class RetentionService
{
    private ActivityLogService $activityLog;

    public function __construct(?ActivityLogService $activityLog = null)
    {
        $this->activityLog = $activityLog ?? app(ActivityLogService::class);
    }

    /**
     * Categorías configuradas.
     *
     * @return array<string, array{dias: int|null, accion: string, descripcion: string}>
     */
    public function categorias(): array
    {
        return config('retention.categorias', []);
    }

    /**
     * ¿Está activada la aplicación automática?
     */
    public function activa(): bool
    {
        return (bool) config('retention.enabled', true);
    }

    /**
     * Aplica una categoría (o todas si no se indica ninguna).
     *
     * @param  string|null  $categoria  Categoría concreta, o null para todas.
     * @param  bool  $simulacion  No escribe cambios, solo informa.
     * @param  string  $origen  'schedule' o 'manual'.
     * @return array<int, RetentionLog>
     */
    public function aplicar(?string $categoria = null, bool $simulacion = false, string $origen = 'manual'): array
    {
        $categorias = $this->categorias();

        if ($categoria !== null) {
            if (! isset($categorias[$categoria])) {
                throw new \InvalidArgumentException(
                    "La categoría de retención [{$categoria}] no existe. Disponibles: ".implode(', ', array_keys($categorias))
                );
            }

            $categorias = [$categoria => $categorias[$categoria]];
        }

        $resultados = [];

        foreach ($categorias as $nombre => $politica) {
            $resultados[] = $this->aplicarCategoria($nombre, $politica, $simulacion, $origen);
        }

        return $resultados;
    }

    /**
     * @param  array{dias: int|null, accion: string, descripcion: string}  $politica
     */
    private function aplicarCategoria(string $categoria, array $politica, bool $simulacion, string $origen): RetentionLog
    {
        $inicio = microtime(true);
        $dias = $politica['dias'] ?? null;
        $accion = $politica['accion'] ?? 'suprimir';

        // Sin plazo definido no se aplica nada: null no significa "cero días".
        if ($dias === null) {
            return $this->registrar($categoria, $accion, 0, now(), 0, [
                'omitida' => 'La categoría no tiene plazo configurado.',
            ], $origen, $simulacion, $inicio);
        }

        $fechaCorte = now()->subDays((int) $dias);

        try {
            $detalle = match ($categoria) {
                'personal' => $this->disociarCandidatos($fechaCorte, $accion, $simulacion),
                'psicometrico' => $this->procesarDatosPsicometricos($fechaCorte, $accion, $simulacion),
                'actividad' => $this->procesarActividad($fechaCorte, $accion, $simulacion),
                'tecnico' => $this->suprimirDatosTecnicos($fechaCorte, $accion, $simulacion),
                default => ['omitida' => "La categoría [{$categoria}] no tiene implementación."],
            };

            $afectados = array_sum($detalle);

            return $this->registrar($categoria, $accion, (int) $dias, $fechaCorte, $afectados, $detalle, $origen, $simulacion, $inicio);
        } catch (Throwable $e) {
            Log::error('Falló la aplicación de la política de retención.', [
                'categoria' => $categoria,
                'accion' => $accion,
                'error' => $e->getMessage(),
            ]);

            $log = $this->registrar($categoria, $accion, (int) $dias, $fechaCorte, 0, [], $origen, $simulacion, $inicio);
            $log->update(['resultado' => 'error', 'error' => $e->getMessage()]);

            return $log->refresh();
        }
    }

    /**
     * Categoría 'personal': disocia la información personal del candidato.
     *
     * Se eliminan los identificadores directos y se conserva el resto del
     * registro, de modo que los datos psicométricos asociados dejan de ser
     * atribuibles a una persona concreta. Es la figura de disociación que
     * contempla la normativa.
     *
     * @return array<string, int>
     */
    private function disociarCandidatos(\DateTimeInterface $fechaCorte, string $accion, bool $simulacion): array
    {
        $consulta = fn (): Builder => Candidate::query()
            ->withTrashed()
            ->whereNull('disociado_at')
            ->where('created_at', '<', $fechaCorte);

        if ($accion === 'suprimir') {
            $cantidad = $consulta()->count();

            if (! $simulacion && $cantidad > 0) {
                $consulta()->forceDelete();
            }

            return ['candidatos_suprimidos' => $cantidad];
        }

        $candidatos = $consulta()->get();
        $disociados = 0;

        foreach ($candidatos as $candidato) {
            if (! $simulacion) {
                DB::table('candidates')
                    ->where('id', $candidato->id)
                    ->update([
                        // Identificadores directos: se sustituyen o se borran.
                        'name' => "Candidato disociado #{$candidato->id}",
                        'email' => "disociado+{$candidato->id}@anonimo.invalid",
                        'dui_nit' => null,
                        'dui_nit_hash' => null,
                        'age' => 0,
                        'occupation' => null,
                        'education_level' => null,
                        'disociado_at' => now(),
                        'updated_at' => now(),
                    ]);

                // El evento se registra sin datos personales: solo la referencia.
                $this->activityLog->log(
                    causer: null,
                    subject: $candidato,
                    event: 'retention_dissociated',
                    description: 'Datos personales disociados por política de retención.',
                    properties: ['categoria' => 'personal', 'candidate_id' => $candidato->id],
                );
            }

            $disociados++;
        }

        return ['candidatos_disociados' => $disociados];
    }

    /**
     * Categoría 'psicometrico': resultados y respuestas.
     *
     * Con 'disociar' los resultados ya quedan desvinculados de toda persona
     * cuando la categoría 'personal' anonimiza al candidato, así que lo que se
     * resuelve aquí es lo que quedó huérfano: resultados cuyo candidato ya no
     * existe. Con 'suprimir' se eliminan los resultados vencidos (y sus
     * respuestas, en cascada por la clave foránea).
     *
     * @return array<string, int>
     */
    private function procesarDatosPsicometricos(\DateTimeInterface $fechaCorte, string $accion, bool $simulacion): array
    {
        // Resultados cuyo candidato ya no existe: no son atribuibles a nadie y no
        // aportan a la estadística.
        $huerfanos = TestResult::query()
            ->whereNotIn('candidate_id', Candidate::query()->withTrashed()->select('id'))
            ->where('created_at', '<', $fechaCorte);

        $cantidadHuerfanos = $huerfanos->count();

        if (! $simulacion && $cantidadHuerfanos > 0) {
            $huerfanos->delete();
        }

        if ($accion !== 'suprimir') {
            return ['resultados_huerfanos_eliminados' => $cantidadHuerfanos];
        }

        $vencidos = TestResult::query()->where('created_at', '<', $fechaCorte);
        $cantidadVencidos = $vencidos->count();

        if (! $simulacion && $cantidadVencidos > 0) {
            $vencidos->delete();
        }

        return [
            'resultados_huerfanos_eliminados' => $cantidadHuerfanos,
            'resultados_suprimidos' => $cantidadVencidos,
        ];
    }

    /**
     * Categoría 'actividad': registros de auditoría.
     *
     * @return array<string, int>
     */
    private function procesarActividad(\DateTimeInterface $fechaCorte, string $accion, bool $simulacion): array
    {
        $consulta = ActivityLog::query()->where('created_at', '<', $fechaCorte);
        $cantidad = $consulta->count();

        if (! $simulacion && $cantidad > 0) {
            $consulta->delete();
        }

        return ['registros_de_actividad_eliminados' => $cantidad];
    }

    /**
     * Categoría 'tecnico': IP, user agent y datos del navegador.
     *
     * Aquí no se elimina la fila: la sesión de test sigue siendo evidencia del
     * proceso psicométrico y solo pierde la traza técnica, que no aporta nada al
     * dato desagregado.
     *
     * @return array<string, int>
     */
    private function suprimirDatosTecnicos(\DateTimeInterface $fechaCorte, string $accion, bool $simulacion): array
    {
        $sesiones = TestSession::query()
            ->withTrashed()
            ->where('created_at', '<', $fechaCorte)
            ->where(function (Builder $q): void {
                $q->whereNotNull('ip_address')
                    ->orWhereNotNull('user_agent')
                    ->orWhereNotNull('browser_info');
            });

        $cantidadSesiones = $sesiones->count();

        if (! $simulacion && $cantidadSesiones > 0) {
            $sesiones->update([
                'ip_address' => null,
                'user_agent' => null,
                'browser_info' => null,
            ]);
        }

        $logs = ActivityLog::query()
            ->where('created_at', '<', $fechaCorte)
            ->where(function (Builder $q): void {
                $q->whereNotNull('ip_address')->orWhereNotNull('user_agent');
            });

        $cantidadLogs = $logs->count();

        if (! $simulacion && $cantidadLogs > 0) {
            $logs->update(['ip_address' => null, 'user_agent' => null]);
        }

        return [
            'sesiones_sin_datos_tecnicos' => $cantidadSesiones,
            'logs_sin_datos_tecnicos' => $cantidadLogs,
        ];
    }

    /**
     * Escribe la evidencia de la operación en `retention_logs`.
     *
     * @param  array<string, mixed>  $detalle
     */
    private function registrar(
        string $categoria,
        string $accion,
        int $dias,
        \DateTimeInterface $fechaCorte,
        int $afectados,
        array $detalle,
        string $origen,
        bool $simulacion,
        float $inicio
    ): RetentionLog {
        return RetentionLog::create([
            'categoria' => $categoria,
            'accion' => $accion,
            'dias_retencion' => $dias,
            'fecha_corte' => $fechaCorte,
            'registros_afectados' => $afectados,
            'detalle' => $detalle,
            'origen' => $origen,
            'simulacion' => $simulacion,
            'resultado' => 'exitosa',
            'duracion_ms' => (int) round((microtime(true) - $inicio) * 1000),
        ]);
    }
}
