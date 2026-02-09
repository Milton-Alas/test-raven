<?php

namespace App\Http\Controllers;

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
        $candidate = Auth::guard('candidate')->user();

        if (!$candidate->test_completed) {
            return redirect()->route('candidate.test.welcome');
        }

        $result = $candidate->latestTestResult;

        return view('candidate.test.completed', compact('candidate', 'result'));
    }
}
