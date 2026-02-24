<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\TestSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TestResultController extends Controller
{
    /**
     * Página de test completado
     */
    public function completed(): View|RedirectResponse
    {
        /** @var Candidate $candidate */
        $candidate = Auth::guard('candidate')->user();

        if (!$candidate || !$candidate->test_completed) {
            return redirect()->route('candidate.test.welcome');
        }

        // 1. Obtener la sesión de test más reciente que esté finalizada.
        // Se busca tanto 'completed' como 'timeout' para cubrir ambos casos.
        $lastSession = TestSession::where('candidate_id', $candidate->id)
            ->whereIn('status', ['completed', 'timeout'])
            ->latest('id')->first();

        // Si no hay una sesión finalizada, redirigir por seguridad.
        if (!$lastSession) {
             return redirect()->route('candidate.test.welcome')
                ->with('error', 'No se encontró una sesión de test finalizada.');
        }

        // 2. Cargar el resultado directamente desde la sesión encontrada.
        // Esto garantiza que se obtenga el resultado correcto sin importar el estado final.
        $result = $lastSession->testResult;

        // 3. Pasar las variables a la vista. La vista no necesita cambios.
        return view('candidate.test.completed', compact('candidate', 'result'));
    }
}
