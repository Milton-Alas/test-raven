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
     * Iniciar una nueva sesión de test para un candidato
     */
    public function startTest(Candidate $candidate): TestSession
    {
        if ($candidate->test_completed) {
            throw new \Exception('El candidato ya ha completado el test.');
        }

        $existingSession = TestSession::where('candidate_id', $candidate->id)
            ->whereIn('status', ['not_started', 'in_progress', 'paused'])
            ->first();

        if ($existingSession) {
            return $this->resumeTest($existingSession);
        }

        $firstQuestion = TestQuestion::ordered()->first();
        if (!$firstQuestion) {
            throw new \Exception('No hay preguntas disponibles.');
        }

        $session = TestSession::create([
            'candidate_id' => $candidate->id,
            'current_question_id' => $firstQuestion->id,
            'started_at' => now(),
            'last_activity_at' => now(),
            'elapsed_time' => 0,
            'time_limit' => 2700,
            'remaining_time' => 2700,
            'status' => 'in_progress',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'browser_info' => [
                'platform' => request()->header('sec-ch-ua-platform'),
                'mobile' => request()->header('sec-ch-ua-mobile'),
            ],
        ]);

        $candidate->update([
            'test_started_at' => now(),
        ]);

        Log::info('Test iniciado', [
            'candidate_id' => $candidate->id,
            'session_id' => $session->id,
        ]);

        return $session;
    }

    /**
     * Reanudar una sesión de test pausada o en progreso
     */
    public function resumeTest(TestSession $session): TestSession
    {
        if (in_array($session->status, ['completed', 'timeout'], true)) {
            throw new \Exception('Esta sesión ya ha sido completada.');
        }

        if ($session->last_activity_at) {
            $elapsedSinceLastActivity = now()->diffInSeconds($session->last_activity_at);
            $newElapsedTime = $session->elapsed_time + $elapsedSinceLastActivity;
            $newRemainingTime = max(0, $session->time_limit - $newElapsedTime);

            $session->update([
                'elapsed_time' => $newElapsedTime,
                'remaining_time' => $newRemainingTime,
                'last_activity_at' => now(),
                'status' => $newRemainingTime > 0 ? 'in_progress' : 'timeout',
            ]);

            if ($newRemainingTime <= 0) {
                return $this->completeTest($session, 'timeout');
            }
        }

        return $session->fresh();
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

        $session->update([
            'last_activity_at' => now(),
        ]);

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
                'last_activity_at' => now(),
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
        DB::beginTransaction();

        try {
            $session->update([
                'completed_at' => now(),
                'status' => $reason,
                'last_activity_at' => now(),
            ]);

            $session->candidate->update([
                'test_completed' => true,
                'test_completed_at' => now(),
            ]);

            $resultCalculator = app(ResultCalculatorService::class);
            $resultCalculator->calculateResult($session);

            DB::commit();

            Log::info('Test completado', [
                'candidate_id' => $session->candidate_id,
                'session_id' => $session->id,
                'reason' => $reason,
            ]);

            return $session->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al completar test', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar tiempo restante de la sesión
     */
    public function updateRemainingTime(TestSession $session, int $elapsedSeconds): void
    {
        $newRemainingTime = max(0, $session->time_limit - $elapsedSeconds);

        $session->update([
            'elapsed_time' => $elapsedSeconds,
            'remaining_time' => $newRemainingTime,
            'last_activity_at' => now(),
        ]);

        if ($newRemainingTime <= 0 && $session->status === 'in_progress') {
            $this->completeTest($session, 'timeout');
        }
    }

    /**
     * Obtener progreso del test
     */
    public function getProgress(TestSession $session): array
    {
        $totalQuestions = 60;
        $answeredCount = TestAnswer::where('test_session_id', $session->id)->count();

        return [
            'total' => $totalQuestions,
            'answered' => $answeredCount,
            'remaining' => $totalQuestions - $answeredCount,
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
