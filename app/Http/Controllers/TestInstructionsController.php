<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TestInstructionsController extends Controller
{
    /**
     * Página de bienvenida / instrucciones
     */
    public function welcome(): View|RedirectResponse
    {
        $candidate = Auth::guard('candidate')->user();

        if ($candidate->test_completed) {
            return redirect()->route('candidate.test.completed');
        }

        return view('candidate.test.welcome', compact('candidate'));
    }
}
