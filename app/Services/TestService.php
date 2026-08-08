<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\TestAnswer;
use App\Models\TestQuestion;
use App\Models\TestSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestService
{
    /**
     * Iniciar una nueva sesión de test para un candidato, con validaciones robustas.
     */
    public function startTest(Candidate $candidate): TestSession
    {
        if ($candidate->test_completed) {
            throw new \Exception('Ya has completado este test.');
        }

        // Buscamos la última sesión del candidato, sin importar el estado.
        $lastSession = $candidate->testSession()->latest('id')->first();

        if ($lastSession) {
            $status = $lastSession->status;

            // Si la última sesión ya fue completada o expiró, no se puede iniciar otra.
            if (in_array($status, ['completed', 'timeout'])) {
                // Aseguramos que el estado del candidato sea consistente.
                if (!$candidate->test_completed) {
                    $candidate->update([
                        'test_completed' => true,
                        'test_completed_at' => $lastSession->completed_at ?? now()
                    ]);
                }
                throw new \Exception('Ya has completado este test y no puedes iniciarlo de nuevo.');
            }

            // Si la sesión está activa, la reanudamos.
            if (in_array($status, ['in_progress', 'paused', 'not_started'])) {
                // Doble verificación: si el tiempo se agotó, finalizarlo ahora.
                $timer = app(TimerService::class);
                if ($timer->hasTimedOut($lastSession->fresh())) {
                    $this->completeTest($lastSession, 'timeout');
                    throw new \Exception('El tiempo para realizar el test ha expirado.');
                }
                return $this->resumeTest($lastSession);
            }
        }

        // Si no hay ninguna sesión, creamos una nueva.
        $firstQuestion = TestQuestion::ordered()->first();
        if (!$firstQuestion) {
            throw new \Exception('No hay preguntas disponibles para iniciar el test.');
        }

        $session = TestSession::create([
            'candidate_id' => $candidate->id,
            'current_question_id' => $firstQuestion->id,
            'started_at' => now(),
            'last_activity_at' => now(),
            'elapsed_time' => 0,
            'time_limit' => 2700, //  2700 segundo produccion 600 test
            'remaining_time' => 2700, //  2700 segundo produccion 600 test
            'status' => 'in_progress',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'browser_info' => [
                'platform' => request()->header('sec-ch-ua-platform'),
                'mobile' => request()->header('sec-ch-ua-mobile'),
            ],
        ]);

        $candidate->update(['test_started_at' => now()]);

        Log::info('Test iniciado', [
            'candidate_id' => $candidate->id,
            'session_id' => $session->id,
        ]);

        return $session;
    }

    /**
     * Reanudar una sesión de test. La lógica de tiempo se ha eliminado
     * para centralizarla en TimerService.
     */
    public function resumeTest(TestSession $session): TestSession
    {
        if (in_array($session->status, ['completed', 'timeout'], true)) {
            throw new \Exception('Esta sesión ya ha sido completada.');
        }

        if ($session->remaining_time <= 0 && $session->status !== 'timeout') {
             return $this->completeTest($session, 'timeout');
        }

        return $session;
    }

    /**
     * Obtener la pregunta actual del test
     */
    public function getCurrentQuestion(TestSession $session): ?TestQuestion
    {
        if ($session->current_question_id) {
            return TestQuestion::with(['series', 'answerOptions'])
                ->find($session->current_question_id);
        }

        $answeredQuestionIds = TestAnswer::where('test_session_id', $session->id)
            ->pluck('test_question_id')
            ->toArray();

        return TestQuestion::with(['series', 'answerOptions'])
            ->whereNotIn('id', $answeredQuestionIds)
            ->ordered()
            ->first();
    }

    /**
     * Obtener la siguiente pregunta
     */
    public function getNextQuestion(TestSession $session): ?TestQuestion
    {
        $currentQuestion = $session->currentQuestion;

        if (!$currentQuestion) {
            return $this->getCurrentQuestion($session);
        }

        $answeredQuestionIds = TestAnswer::where('test_session_id', $session->id)
            ->pluck('test_question_id')
            ->toArray();

        return TestQuestion::with(['series', 'answerOptions'])
            ->where('global_order', '>', $currentQuestion->global_order)
            ->whereNotIn('id', $answeredQuestionIds)
            ->ordered()
            ->first();
    }

    /**
     * Guardar respuesta de una pregunta
     */
    public function saveAnswer(
        TestSession $session,
        TestQuestion $question,
        int $selectedAnswer,
        int $timeSpent = 0
    ): TestAnswer {
        $series = $question->series->code;
        $maxOptions = in_array($series, ['C', 'D', 'E']) ? 8 : 6;

        if ($selectedAnswer < 1 || $selectedAnswer > $maxOptions) {
            throw new \Exception("Respuesta inválida. Debe estar entre 1 y {$maxOptions}.");
        }

        $isCorrect = ($selectedAnswer === $question->correct_answer);

        $answer = TestAnswer::where('test_session_id', $session->id)
            ->where('test_question_id', $question->id)
            ->first();

        if ($answer) {
            $answer->selected_answer = $selectedAnswer;
            $answer->is_correct = $isCorrect;
            $answer->time_spent = $timeSpent;
            $answer->answered_at = now();
            $answer->was_changed = true;
            $answer->attempt_number = $answer->attempt_number + 1;
        } else {
            $answer = new TestAnswer();
            $answer->test_session_id = $session->id;
            $answer->test_question_id = $question->id;
            $answer->selected_answer = $selectedAnswer;
            $answer->is_correct = $isCorrect;
            $answer->time_spent = $timeSpent;
            $answer->answered_at = now();
            $answer->attempt_number = 1;
            $answer->was_changed = false;
        }
        
        $answer->save();

        return $answer;
    }

    /**
     * Avanzar a la siguiente pregunta
     */
    public function moveToNextQuestion(TestSession $session): bool
    {
        $nextQuestion = $this->getNextQuestion($session);

        if ($nextQuestion) {
            $session->update([
                'current_question_id' => $nextQuestion->id,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Completar el test
     */
    public function completeTest(TestSession $session, string $reason = 'completed'): TestSession
    {
        // Paso 1: Marcar la sesión como completada (fuera de la transacción de cálculo)
        // Esto es crítico para asegurar que el estado del test se actualice incluso si el cálculo falla.
        if (!in_array($session->status, ['completed', 'timeout'])) {
            $updates = [
                'completed_at' => now(),
                'status' => $reason,
                'last_activity_at' => now(),
            ];
    
            if ($reason === 'timeout') {
                $updates['remaining_time'] = 0;
            }
    
            $session->update($updates);
    
            $session->candidate->update([
                'test_completed' => true,
                'test_completed_at' => now(),
            ]);

            Log::info('Test marcado como finalizado.', [
                'candidate_id' => $session->candidate_id,
                'session_id' => $session->id,
                'reason' => $reason,
            ]);
        }

        // Paso 2: Intentar calcular los resultados en un proceso separado y transaccional.
        // Si esto falla, no afectará el estado 'completado' del test.
        try {
            $resultCalculator = app(ResultCalculatorService::class);
            // Usamos fresh() para asegurar que el calculador recibe el estado 'completed'/'timeout' que acabamos de guardar
            $resultCalculator->calculateResult($session->fresh());

        } catch (\Exception $e) {
            Log::error('El cálculo de resultados falló DESPUÉS de finalizar la sesión.', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Log more details
            ]);
            // No relanzamos la excepción para no romper el flujo del controlador.
            // El test ya está marcado como completado, que es lo más importante.
        }

        return $session->fresh();
    }

    /**
     * Obtener progreso del test. Ahora sincronizado con la pregunta actual.
     */
    public function getProgress(TestSession $session, TestQuestion $question): array
    {
        $totalQuestions = 60;
        $answeredCount = TestAnswer::where('test_session_id', $session->id)->count();
        $currentDisplay = min($totalQuestions, $answeredCount + 1);

        return [
            'total' => $totalQuestions,
            'answered' => $answeredCount,
            'remaining' => $totalQuestions - $answeredCount,
            'current_display' => $currentDisplay,
            'percentage' => $totalQuestions > 0
                ? round(($answeredCount / $totalQuestions) * 100, 2)
                : 0.0,
        ];
    }

    /**
     * Verificar si el test está completo (todas las preguntas respondidas)
     */
    public function isTestComplete(TestSession $session): bool
    {
        $totalQuestions = 60;
        $answeredCount = TestAnswer::where('test_session_id', $session->id)->count();

        return $answeredCount >= $totalQuestions;
    }
}
