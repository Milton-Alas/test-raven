<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\TestQuestion;
use App\Services\TestService;
use App\Services\TimerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TestController extends Controller
{
    private TestService $testService;
    private TimerService $timerService;

    public function __construct(TestService $testService, TimerService $timerService)
    {
        $this->testService = $testService;
        $this->timerService = $timerService;
    }

    /**
     * Iniciar el test
     */
    public function start(Request $request): RedirectResponse
    {
        /** @var Candidate $candidate */
        $candidate = Auth::guard('candidate')->user();

        try {
            $this->testService->startTest($candidate);
            return redirect()->route('candidate.test.question')
                ->with('success', 'Test iniciado. ¡Buena suerte!');
        } catch (\Exception $e) {
            Log::error('Error al iniciar test: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mostrar pregunta actual
     */
    public function showQuestion(): View|RedirectResponse
    {
        /** @var Candidate $candidate */
        $candidate = Auth::guard('candidate')->user();
        $session = $candidate->testSession()
            ->whereIn('status', ['not_started', 'in_progress', 'paused'])
            ->latest('id')
            ->first();

        if (!$session) {
            return redirect()->route('candidate.test.welcome');
        }

        // CORRECCIÓN 5: Verificación de timeout a nivel de backend
        if ($this->timerService->hasTimedOut($session)) {
            $this->testService->completeTest($session, 'timeout');
            return redirect()->route('candidate.test.completed');
        }

        if ($session->is_completed) {
            return redirect()->route('candidate.test.completed');
        }

        $session = $this->testService->resumeTest($session);

        if ($session->status === 'timeout') {
            return redirect()->route('candidate.test.completed')
                ->with('warning', 'El tiempo del test se ha agotado.');
        }

        $question = $this->testService->getCurrentQuestion($session);

        if (!$question) {
            $this->testService->completeTest($session);
            return redirect()->route('candidate.test.completed');
        }

        $timerData = $this->timerService->getTimerData($session);
        // Modificado para pasar la pregunta al método getProgress
        $progress = $this->testService->getProgress($session, $question);

        return view('candidate.test.question', compact(
            'session',
            'question',
            'timerData',
            'progress'
        ));
    }

    /**
     * Guardar respuesta y avanzar (AJAX)
     */
    public function saveAnswer(Request $request): JsonResponse
    {
        $question = TestQuestion::find($request->input('question_id'));
        $maxAnswers = 6; // Default
        if ($question) {
            $seriesCode = $question->series->code ?? null;
            $maxAnswers = in_array($seriesCode, ['C', 'D', 'E']) ? 8 : 6;
        }

        $request->validate([
            'question_id' => 'required|exists:test_questions,id',
            'answer' => 'required|integer|min:1|max:' . $maxAnswers,
            'time_spent' => 'nullable|integer',
        ]);

        /** @var Candidate $candidate */
        $candidate = Auth::guard('candidate')->user();
        $session = $candidate->testSession()
            ->whereIn('status', ['not_started', 'in_progress', 'paused'])
            ->latest('id')
            ->first();

        if (!$session || $session->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión inválida o ya completada.',
            ], 400);
        }

        try {
            // Chequeo PREVIO de timeout. Si ya se acabó el tiempo, no guardar.
            if ($this->timerService->hasTimedOut($session->fresh())) {
                $this->testService->completeTest($session, 'timeout');
                return response()->json([
                    'success' => false,
                    'timeout' => true,
                    'message' => 'El tiempo ya se había agotado. No se pudo guardar la respuesta.',
                    'redirect' => route('candidate.test.completed'),
                ]);
            }

            // 1. Guardar la respuesta PRIMERO.
            $timeSpent = (int) ($request->time_spent ?? 0);
            $question = TestQuestion::findOrFail($request->question_id);
            $this->testService->saveAnswer(
                $session,
                $question,
                (int) $request->answer,
                $timeSpent
            );

            // 2. Deducir tiempo y refrescar la sesión.
            $this->timerService->deductTime($session, $timeSpent);
            $freshSession = $session->fresh();

            // 3. Revisar si el test terminó DESPUÉS de guardar la respuesta.
            $hasNext = $this->testService->moveToNextQuestion($freshSession);
            $isComplete = $this->testService->isTestComplete($freshSession);

            // Chequeo de timeout POSTERIOR al guardado
            if ($this->timerService->hasTimedOut($freshSession)) {
                $this->testService->completeTest($freshSession, 'timeout');
                return response()->json([
                    'success' => true, // La respuesta se guardó
                    'completed' => true,
                    'timeout' => true,
                    'message' => 'Respuesta guardada, pero el tiempo del test ha finalizado.',
                    'redirect' => route('candidate.test.completed'),
                ]);
            }
            
            // Chequeo de completitud normal
            if ($isComplete || !$hasNext) {
                $this->testService->completeTest($freshSession);
                return response()->json([
                    'success' => true,
                    'completed' => true,
                    'message' => 'Test completado.',
                    'redirect' => route('candidate.test.completed'),
                ]);
            }

            // Si nada de lo anterior ocurrió, es una respuesta normal.
            return response()->json([
                'success' => true,
                'completed' => false,
                'message' => 'Respuesta guardada.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al guardar respuesta: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la respuesta.',
            ], 500);
        }
    }

    /**
     * Manejar el timeout del test (AJAX)
     */
    public function handleTimeout(Request $request): JsonResponse
    {
        $candidate = Auth::guard('candidate')->user();

        $session = $candidate->testSession()
            ->whereIn('status', ['in_progress', 'paused'])
            ->latest('id')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No hay sesión activa.'
            ], 404);
        }

        try {
            $this->testService->completeTest($session, 'timeout');

            return response()->json([
                'success' => true,
                'redirect' => route('candidate.test.completed')
            ]);

        } catch (\Exception $e) {
            Log::error('Error al finalizar test por timeout: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno.'
            ], 500);
        }
    }

    /**
     * Obtener datos del timer (AJAX)
     */
    public function getTimerData(Request $request): JsonResponse
    {
        /** @var Candidate $candidate */
        $candidate = Auth::guard('candidate')->user();
        $session = $candidate->testSession()
            ->whereIn('status', ['not_started', 'in_progress', 'paused'])
            ->latest('id')
            ->first();

        if (!$session) {
            return response()->json(['error' => 'No hay sesión activa'], 404);
        }

        $timerData = $this->timerService->getTimerData($session);

        return response()->json($timerData);
    }
}
