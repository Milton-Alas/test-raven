<?php

namespace App\Services;

use App\Models\TestSession;
use Carbon\Carbon;

class TimerService
{
    /**
     * Obtener tiempo restante en segundos
     */
    public function getRemainingTime(TestSession $session): int
    {
        return $this->updateTiming($session);
    }

    /**
     * Verificar si el tiempo se ha agotado
     */
    public function hasTimedOut(TestSession $session): bool
    {
        return $this->getRemainingTime($session) <= 0;
    }
    
    /**
     * Deducir tiempo de la sesión (Penalización).
     * 
     * Estrategia: Reducimos el 'time_limit' de la sesión.
     * Esto hace que el cálculo 'time_limit - elapsed' disminuya instantáneamente
     * sin romper la lógica de seguridad de updateTiming.
     */
    /**
     * Deducir tiempo de la sesión (Penalización).
     * Actualiza remaining_time, time_limit y elapsed_time.
     */
    public function deductTime(TestSession $session, int $secondsToDeduct): int
    {
        // 1. Sincronizar estado actual
        $currentRemaining = $this->getRemainingTime($session);

        // 2. Calcular nuevos valores
        $newRemaining = max(0, $currentRemaining - $secondsToDeduct);
        
        // Reducimos el límite total para que el reloj fluya naturalmente
        $currentTimeLimit = (int) ($session->time_limit ?? 0);
        $newTimeLimit = max(0, $currentTimeLimit - $secondsToDeduct);

        // Calculamos el tiempo transcurrido sincronizado con el nuevo límite
        // Fórmula: Tiempo Total - Tiempo Restante = Tiempo Usado
        $newElapsed = $newTimeLimit - $newRemaining;

        // 3. Guardar todo en la base de datos
        $session->update([
            'time_limit' => $newTimeLimit,      // Para que el reloj no se congele
            'remaining_time' => $newRemaining,  // El tiempo real que queda
            'elapsed_time' => $newElapsed,      // El tiempo efectivamente consumido
            'last_activity_at' => now(),
        ]);

        return $newRemaining;
    }

    /**
     * Actualizar tiempo en DB y devolver el restante.
     * Sincroniza el reloj del servidor con la base de datos.
     */
    public function updateTiming(TestSession $session): int
    {
        return $this->syncTiming($session);
    }

    /**
     * Sincroniza el tiempo persistido con el reloj del servidor y, opcionalmente,
     * con el menor tiempo reportado por el cliente para evitar "reinicios" visuales
     * al recargar la siguiente pregunta.
     */
    public function syncTiming(TestSession $session, ?int $clientRemaining = null): int
    {
        // 2700 seg = 45 min (prod) | 600 seg = 10 min (test)
        $timeLimit = (int) ($session->time_limit ?? 600); 
        
        // Si es null, es la primera vez, usamos el límite total
        $persistedRemaining = is_null($session->remaining_time)
            ? $timeLimit
            : (int) $session->remaining_time;

        // Si el examen ya terminó, no recalculamos más
        if (in_array($session->status, ['completed', 'timeout'], true)) {
            return max(0, min($timeLimit, $persistedRemaining));
        }

        // Cálculo absoluto: ¿Cuánto tiempo real ha pasado desde el inicio?
        $startedAt = $session->started_at ?? $session->created_at ?? now();
        $elapsedFromStart = now()->diffInSeconds($startedAt);
        $calculatedRemaining = max(0, $timeLimit - $elapsedFromStart);

        // SEGURIDAD: Nunca permitir que el tiempo "suba".
        // Si la BD dice que quedan 500s, pero el reloj dice 550s (por alguna inconsistencia),
        // nos quedamos con 500s. Respetamos también penalizaciones manuales (deductTime).
        $newRemaining = min($persistedRemaining, $calculatedRemaining);

        if (!is_null($clientRemaining)) {
            $newRemaining = min($newRemaining, max(0, $clientRemaining));
        }
        
        $totalElapsedTime = $timeLimit - $newRemaining;

        $updates = [
            'remaining_time' => $newRemaining,
            'elapsed_time' => $totalElapsedTime,
            'last_activity_at' => now(),
        ];

        // Si el tiempo llega a 0, lo aseguramos
        if ($newRemaining <= 0) {
            $updates['remaining_time'] = 0;
        }

        $session->update($updates);

        return $newRemaining;
    }

    /**
     * Formatear tiempo en formato MM:SS
     */
    public function formatTime(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Obtener datos del timer listos para el Frontend (Web Worker).
     * Genera la fecha absoluta de expiración.
     */
    public function getTimerData(TestSession $session): array
    {
        // 1. Sincronizar y obtener segundos restantes reales
        $freshSession = $session->fresh();
        $remainingSeconds = $this->updateTiming($freshSession);
        
        $totalSeconds = (int) ($freshSession->time_limit ?? 2700);
        $elapsedSeconds = max(0, $totalSeconds - $remainingSeconds);
        $percentageRemaining = $totalSeconds > 0
            ? round(($remainingSeconds / $totalSeconds) * 100, 2)
            : 0.0;

        // 2. CALCULAR EXPIRES_AT (ISO 8601)
        // Sumamos el tiempo restante a la hora actual del servidor.
        // Esto es CRUCIAL para el Web Worker.
        $expiresAt = now()->addSeconds($remainingSeconds)->toIso8601String();

        return [
            'remaining_seconds' => $remainingSeconds,
            'remaining_formatted' => $this->formatTime($remainingSeconds),
            'expires_at' => $expiresAt, // Para el atributo data-expires-at
            'elapsed_seconds' => $elapsedSeconds,
            'total_seconds' => $totalSeconds,
            'percentage_remaining' => $percentageRemaining,
            'is_warning' => $remainingSeconds <= 300,
            'is_critical' => $remainingSeconds <= 60,
            'has_timed_out' => $remainingSeconds <= 0,
        ];
    }
}