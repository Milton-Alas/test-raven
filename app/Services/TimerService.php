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
     * Actualizar tiempo en DB y devolver el restante
     */
    public function updateTiming(TestSession $session): int
    {
        if (in_array($session->status, ['completed', 'timeout'], true)) {
            return 0;
        }

        if (!$session->started_at) {
            return (int) $session->time_limit;
        }

        $totalSeconds = (int) $session->time_limit;
        $elapsedFromStart = now()->diffInSeconds($session->started_at);
        $remaining = max(0, $totalSeconds - $elapsedFromStart);

        $updates = [
            'elapsed_time' => $elapsedFromStart,
            'remaining_time' => $remaining,
            'last_activity_at' => now(),
        ];

        if ($remaining <= 0) {
            $updates['status'] = 'timeout';
            $updates['completed_at'] = now();
        }

        $session->update($updates);

        return $remaining;
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
        $remainingSeconds = $this->updateTiming($session);
        $totalSeconds = (int) $session->time_limit;
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
