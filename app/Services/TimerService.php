<?php

namespace App\Services;

use App\Models\TestSession;

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
     * Deducir tiempo de la sesión (ej. al guardar una respuesta)
     */
    public function deductTime(TestSession $session, int $secondsToDeduct): int
    {
        $currentRemaining = $this->getRemainingTime($session);
        $newRemaining = max(0, $currentRemaining - $secondsToDeduct);

        $session->update(['remaining_time' => $newRemaining]);

        return $newRemaining;
    }

    /**
     * Actualizar tiempo en DB y devolver el restante.
     * Esta es la nueva lógica de cuenta atrás.
     */
    public function updateTiming(TestSession $session): int
    {
        /*2700 segundos = 45 minutos produccion 
        * 600 segundos = 10 minuto test
        */
        $timeLimit = (int) ($session->time_limit ?? 600); 
        $persistedRemaining = is_null($session->remaining_time)
            ? $timeLimit
            : (int) $session->remaining_time;

        if (in_array($session->status, ['completed', 'timeout'], true)) {
            return max(0, min($timeLimit, $persistedRemaining));
        }

        $startedAt = $session->started_at ?? $session->created_at ?? now();
        $elapsedFromStart = now()->diffInSeconds($startedAt);
        $calculatedRemaining = max(0, $timeLimit - $elapsedFromStart);

        // Nunca permitir que el tiempo "suba" por datos inconsistentes.
        $newRemaining = min($persistedRemaining, $calculatedRemaining);
        $totalElapsedTime = $timeLimit - $newRemaining;

        $updates = [
            'remaining_time' => $newRemaining,
            'elapsed_time' => $totalElapsedTime,
            'last_activity_at' => now(),
        ];

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
     * Obtener datos del timer para el frontend
     */
    public function getTimerData(TestSession $session): array
    {
        $freshSession = $session->fresh();
        $remainingSeconds = $this->updateTiming($freshSession);
        /*2700 segundos = 45 minutos produccion 
        * 600 segundos = 10 minuto test
        */
        $totalSeconds = (int) ($freshSession->time_limit ?? 600);
        $elapsedSeconds = max(0, $totalSeconds - $remainingSeconds);
        $percentageRemaining = $totalSeconds > 0
            ? round(($remainingSeconds / $totalSeconds) * 100, 2)
            : 0.0;

        return [
            'remaining_seconds' => $remainingSeconds,
            'remaining_formatted' => $this->formatTime($remainingSeconds),
            'elapsed_seconds' => $elapsedSeconds,
            'total_seconds' => $totalSeconds,
            'percentage_remaining' => $percentageRemaining,
            'is_warning' => $remainingSeconds <= 300,
            'is_critical' => $remainingSeconds <= 60,
            'has_timed_out' => $remainingSeconds <= 0,
        ];
    }
}
