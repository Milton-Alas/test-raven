<?php

use App\Http\Controllers\CandidateAuthController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TestInstructionsController;
use App\Http\Controllers\TestResultController;
use Illuminate\Support\Facades\Route;

// Redirige la raíz a la página de login
Route::get('/', function () {
    return to_route('login');
});

// Rutas de autenticación para candidatos (invitados)
Route::middleware('guest:candidate')->group(function () {
    // Login
    Route::get('login', [CandidateAuthController::class, 'create'])->name('login');
    Route::post('login', [CandidateAuthController::class, 'store']);

    // Register
    Route::get('register', [CandidateAuthController::class, 'createRegister'])->name('register');
    Route::post('register', [CandidateAuthController::class, 'storeRegister']);
});

// Rutas protegidas para candidatos autenticados
Route::middleware('auth:candidate')->group(function () {
    // Instrucciones / bienvenida
    Route::get('/instrucciones', [TestInstructionsController::class, 'welcome'])
        ->name('candidate.test.welcome');

    // Flujo del test
    Route::post('/test/start', [TestController::class, 'start'])
        ->name('candidate.test.start');
    Route::get('/test/question', [TestController::class, 'showQuestion'])
        ->name('candidate.test.question');
    Route::post('/test/answer', [TestController::class, 'saveAnswer'])
        ->name('candidate.test.answer');
    Route::post('/test/timeout', [TestController::class, 'handleTimeout'])
        ->name('candidate.test.timeout');
    Route::get('/test/timer', [TestController::class, 'getTimerData'])
        ->name('candidate.test.timer');

    // Resultados
    Route::get('/test/completed', [TestResultController::class, 'completed'])
        ->name('candidate.test.completed');

    // Ruta para cerrar sesión
    Route::post('logout', [CandidateAuthController::class, 'destroy'])->name('logout');
});
